<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\NotSupportedException;

/**
 * Provides a generic view for streams.
 */
abstract class Stream
{
    /**
     * Returns true if the stream is readable.
     *
     * @return bool
     */
    abstract public function canRead(): bool;

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    abstract public function canWrite(): bool;

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    abstract public function canSeek(): bool;

    /**
     * Returns true if the current position is at the end of the stream.
     *
     * @return bool
     */
    abstract public function endOfStream(): bool;

    /**
     * Gets the stream size in bytes.
     *
     * @return int
     */
    abstract public function getSize(): int;

    /**
     * Sets the size of the stream.
     *
     * @param int $size The new size. If the new size is less than the current size, the stream will be truncated.
     *
     * @throws IOException
     */
    abstract public function setSize(int $size): void;

    /**
     * Gets the current position in the stream.
     *
     * @return int
     * @throws IOException
     */
    abstract public function getPosition(): int;

    /**
     * Sets the position in the stream.
     *
     * @param int $position The new position.
     *
     * @throws IOException
     */
    abstract public function setPosition(int $position): void;

    /**
     * Seeks on the stream.
     *
     * @param int $offset The new position within the stream, relative to $whence value.
     * @param int $whence The seek reference point.
     *
     * @throws IOException
     */
    abstract public function seek(int $offset, int $whence): void;

    /**
     * Reads a block of bytes from the stream.
     *
     * @param string|null $output The data read from the stream.
     * @param int $length The maximum number of bytes to read.
     *
     * @return int The number of bytes read.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function read(?string &$output, int $length): int;

    /**
     * Reads a byte (or a char) from the stream.
     *
     * @return string|null The character or NULL if the end of the stream has been reached.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function readChar(): ?string;

    /**
     * Reads the stream until it finds an end-of-line sequence.
     *
     * @return string The data read from the stream.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function readLine(): string;

    /**
     * Read the entire content of the stream to the end.
     *
     * @return string The data read from the stream.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function readToEnd(): string;

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to be written.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop
     * until the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function write(string $data, int $length = -1): int;

    /**
     * Writes a string to the stream, followed by an end-of-line sequence.
     *
     * @param string $data The string to be written.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop
     * until the end of $data is reached. This value does not count the length of the line terminator.
     *
     * @return int The number of bytes written plus the length of the end-of-line sequence.
     * @throws IOException
     * @throws NotSupportedException
     */
    abstract public function writeLine(string $data, int $length = -1): int;

    /**
     * Forces all buffered output to be written into the destination pointed by the stream.
     *
     * @throws IOException
     */
    abstract public function flush(): void;

    /**
     * Closes the stream.
     */
    abstract public function close(): void;

    /**
     * Writes the stream contents into another stream. Copying begins at the current position in the stream
     * and does not reset the position of the destination stream after the copy operation is complete.
     *
     * @param Stream $stream The stream to copy the contents of this stream to.
     * @param int $buffer_size The size of the buffer. This value must be greater than zero.
     *
     * @throws IOException If this stream is not readable or the destination stream is not writable.
     * @throws InvalidArgumentException If the buffer size is less than or equal to zero.
     * @throws NotSupportedException If the stream is not readable or the destination stream is not writable.
     */
    public function copyTo(Stream $stream, int $buffer_size = 81920): void
    {
        $this->ensureCanRead();
        $stream->ensureCanWrite();

        if ($buffer_size <= 0) {
            throw new InvalidArgumentException('Buffer size must be greater than zero');
        }

        while (!$this->endOfStream()) {
            $bytes_read = $this->read($data, $buffer_size);
            $stream->write($data, $bytes_read);
        }
    }

    /**
     * Ensures that the stream is readable.
     * Throws an exception if the stream is not readable.
     *
     * @throws NotSupportedException
     */
    protected function ensureCanRead(): void
    {
        if (!$this->canRead()) {
            throw new NotSupportedException('The stream does not support reading');
        }
    }

    /**
     * Ensures that the stream is writable.
     * Throws an exception if the stream is not writable.
     *
     * @throws NotSupportedException
     */
    protected function ensureCanWrite(): void
    {
        if (!$this->canWrite()) {
            throw new NotSupportedException('The stream does not support writing');
        }
    }
}
