<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\Closeable;
use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use function sprintf;

/**
 * Defines an interface for common IO stream manipulation.
 */
abstract class Stream implements Closeable
{
    /**
     * Throws an exception. Streams do not support serialization.
     *
     * @return array
     */
    public function __serialize(): array
    {
        throw new NotSupportedException(
            sprintf(
                'Class %s does not support serialization',
                static::class,
            ),
        );
    }

    /**
     * Returns true if the stream is readable.
     *
     * @return bool
     */
    abstract public function isReadable(): bool;

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    abstract public function isWritable(): bool;

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    abstract public function isSeekable(): bool;

    /**
     * Returns true if the current position is at the end of the stream.
     *
     * @return bool
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function endOfStream(): bool;

    /**
     * Gets the size of the stream.
     *
     * @return int
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function getSize(): int;

    /**
     * Sets the size of the stream.
     *
     * @param int $size The size of the stream in bytes.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function setSize(int $size): void;

    /**
     * Gets the current position within the stream.
     *
     * @return int
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function getPosition(): int;

    /**
     * Sets the current position within the stream.
     *
     * @param int $position The position offset.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function setPosition(int $position): void;

    /**
     * Seeks on the stream moving the current position.
     *
     * @param int $offset The seek offset.
     * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function seek(int $offset, int $whence): void;

    /**
     * Reads up to $length bytes from the stream.
     *
     * @param int $length The amount of bytes to read.
     *
     * @return string The data read from the stream or an empty string if the end of stream was reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function read(int $length): string;

    /**
     * Reads a byte from the stream.
     *
     * @return string|null The read byte or NULL if the end of stream was reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function readChar(): ?string;

    /**
     * Reads a line from the stream.
     *
     * @return string|null The data read from the stream or NULL if the end of stream has been reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function readLine(): ?string;

    /**
     * Reads the stream until it reaches the end of stream.
     *
     * @return string The data read from the stream.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function readToEnd(): string;

    /**
     * Writes up to $length bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The amount of bytes to write. If $length is less than zero, the whole string will be written.
     *
     * @return int The number of bytes written.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function write(string $data, int $length = -1): int;

    /**
     * Writes to the stream followed by an end-of-line sequence.
     *
     * @param string $data The data to write.
     *
     * @return int The number of bytes written.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function writeLine(string $data): int;

    /**
     * Forces all buffered data to be written to the underlying stream implementation.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    abstract public function flush(): void;

    /**
     * Copies data from this stream to another.
     *
     * @param Stream $destination The destination stream where the copied data will be written.
     * @param int $buffer_size The size of the internal buffer.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    public function copyTo(Stream $destination, int $buffer_size = 81920): void
    {
        $this->ensureStreamIsReadable();
        $destination->ensureStreamIsWritable();

        if ($buffer_size <= 0) {
            throw new InvalidArgumentException(
                sprintf('Buffer size \'%d\' is not valid. Must be an integer greater than zero', $buffer_size),
            );
        }

        while (!$this->endOfStream()) {
            $data = $this->read($buffer_size);
            $destination->write($data);
        }
    }

    /**
     * Ensures that the stream is open before attempting to execute any operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract protected function ensureStreamIsOpen(): void;

    /**
     * Ensures that the stream is open and is readable before attempting to execute any read operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    protected function ensureStreamIsReadable(): void
    {
        $this->ensureStreamIsOpen();
        if (!$this->isReadable()) {
            throw new InvalidOperationException('Stream does not support read');
        }
    }

    /**
     * Ensures that the stream is open and is writable before attempting to execute any write operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    protected function ensureStreamIsWritable(): void
    {
        $this->ensureStreamIsOpen();
        if (!$this->isWritable()) {
            throw new InvalidOperationException('Stream does not support write');
        }
    }

    /**
     * Ensures that the stream is open and is seekable before attempting to execute any seek operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed or does not support seeking.
     */
    abstract protected function ensureStreamIsSeekable(): void;
}
