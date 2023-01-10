<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\Closeable;
use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use function sprintf;

/**
 * Defines an interface for streams.
 */
abstract class Stream implements Closeable
{
    /**
     * Throws a NotSupportedException.
     *
     * @return array
     */
    public function __serialize(): array
    {
        throw new NotSupportedException(
            sprintf(
                'Class %s does not support serialization',
                static::class
            )
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
     * Returns true if the current position in the stream equals the size of the stream.
     *
     * @return bool
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function endOfStream(): bool;

    /**
     * Returns the size of the stream in bytes.
     *
     * @return int
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function getSize(): int;

    /**
     * Sets the size, in bytes, of the stream.
     *
     * @param int $size The new size.
     *
     * @return void
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function setSize(int $size): void;

    /**
     * Gets the current position in the stream.
     *
     * @return int
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function getPosition(): int;

    /**
     * Sets the current position in the stream to the given value.
     *
     * @param int $position The position in the stream.
     *
     * @return void
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function setPosition(int $position): void;

    /**
     * Sets the current position in the stream, relative to $whence.
     *
     * @param int $offset The position relative to $whence from which to begin seeking.
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
     *
     * @return void
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed.
     */
    abstract public function seek(int $offset, int $whence): void;

    /**
     * Reads a block of bytes from the stream.
     *
     * @param int $length The maximum number of bytes to read.
     *
     * @return string The data read from the stream.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    abstract public function read(int $length): string;

    /**
     * Reads a char from the stream.
     *
     * @return string|null The read character or null if the end of the stream has been reached.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    abstract public function readChar(): ?string;

    /**
     * Reads the stream until an end-of-line sequence is found.
     *
     * @return string|null The data read from the stream or null if the end of the stream has been reached.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    abstract public function readLine(): ?string;

    /**
     * Reads the remainder of the stream into a string.
     *
     * @return string The data read from the stream.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    abstract public function readToEnd(): string;

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop until
     *     the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    abstract public function write(string $data, int $length = -1): int;

    /**
     * Writes a string to the stream, followed by an end-of-line sequence.
     *
     * @param string $data The string to write.
     *
     * @return int The number of bytes written, including the length of the end-of-line sequence.
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    abstract public function writeLine(string $data): int;

    /**
     * Forces any buffered data to be written into the stream.
     *
     * @return void
     * @throws IOException
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    abstract public function flush(): void;

    /**
     * Reads the data from the stream and writes it to another stream.
     * Copying begins at the current position in this stream and does not reset the position of the destination stream
     * after the copy is complete.
     *
     * @param Stream $destination The stream where the copy will be written.
     * @param int $buffer_size The size of the copy buffer.
     *
     * @return void
     * @throws IOException
     * @throws InvalidArgumentException if the buffer size is less than or equal to zero.
     * @throws InvalidOperationException if the stream is not readable or the destination stream is not writable.
     */
    public function copyTo(Stream $destination, int $buffer_size = 81920): void
    {
        $this->ensureStreamIsReadable();
        $destination->ensureStreamIsWritable();

        if ($buffer_size <= 0) {
            throw new InvalidArgumentException(
                sprintf('Buffer size \'%d\' is not valid. Must be an integer greater than zero', $buffer_size)
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
