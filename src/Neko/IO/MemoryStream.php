<?php declare(strict_types=1);
namespace Neko\IO;

use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function ftruncate;
use function fwrite;
use function stream_get_contents;
use function strlen;
use const SEEK_SET;

/**
 * Creates a Stream in memory.
 */
class MemoryStream extends Stream
{
    private mixed $memory;

    /**
     * MemoryStream constructor.
     *
     * @throws IOException If the stream could not be open.
     */
    public function __construct()
    {
        $this->memory = fopen('php://memory', 'r+b');
        if ($this->memory === false) {
            IOException::throwFromLastError();
        }
    }

    /**
     * Returns true if the stream is readable.
     *
     * @return bool
     */
    public function canRead(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    public function canSeek(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the current position is at the end of the stream.
     *
     * @return bool
     * @throws InvalidOperationException If the stream is closed.
     */
    public function endOfStream(): bool
    {
        $this->ensureStreamIsOpen();
        return feof($this->memory);
    }

    /**
     * Gets the stream size in bytes.
     *
     * @return int
     * @throws InvalidOperationException If the stream is closed.
     */
    public function getSize(): int
    {
        $this->ensureStreamIsOpen();
        return fstat($this->memory)['size'];
    }

    /**
     * Sets the size of the stream.
     *
     * @param int $size The new size. If the new size is less than the current size, the stream will be truncated.
     *
     * @throws InvalidOperationException If the stream is closed.
     */
    public function setSize(int $size): void
    {
        $this->ensureStreamIsOpen();
        ftruncate($this->memory, $size);
    }

    /**
     * Gets the current position in the stream.
     *
     * @return int
     * @throws InvalidOperationException If the stream is closed.
     */
    public function getPosition(): int
    {
        $this->ensureStreamIsOpen();
        return ftell($this->memory);
    }

    /**
     * Sets the position in the stream.
     *
     * @param int $position The new position.
     *
     * @throws InvalidOperationException If the stream is closed.
     */
    public function setPosition(int $position): void
    {
        $this->seek($position, SEEK_SET);
    }

    /**
     * Seeks on the stream.
     *
     * @param int $offset The new position within the stream, relative to $whence value.
     * @param int $whence The seek reference point.
     *
     * @throws InvalidOperationException If the stream is closed.
     */
    public function seek(int $offset, int $whence): void
    {
        $this->ensureStreamIsOpen();
        fseek($this->memory, $offset, $whence);
    }

    /**
     * Reads a block of bytes from the stream.
     *
     * @param int $length The maximum number of bytes to read.
     *
     * @return string A string containing the read data.
     * @throws InvalidOperationException If the stream is closed.
     */
    public function read(int $length): string
    {
        $this->ensureStreamIsOpen();
        return fread($this->memory, $length);
    }

    /**
     * Reads the stream until it finds an end-of-line sequence.
     *
     * @return string A string containing the read data.
     * @throws InvalidOperationException If the stream is closed.
     */
    public function readLine(): string
    {
        $this->ensureStreamIsOpen();
        return fgets($this->memory);
    }

    /**
     * Read the entire content of the stream to the end.
     *
     * @return string A string containing the read data.
     * @throws InvalidOperationException If the stream is closed.
     */
    public function readToEnd(): string
    {
        $this->ensureStreamIsOpen();
        return stream_get_contents($this->memory);
    }

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to be written.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop
     * until the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws InvalidOperationException If the stream is closed.
     */
    public function write(string $data, int $length = -1): int
    {
        $this->ensureStreamIsOpen();
        $length = $length > -1 ? $length : strlen($data);
        return fwrite($this->memory, $data, $length);
    }

    /**
     * Writes a string to the stream, followed by an end-of-line sequence.
     *
     * @param string $data The string to be written.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop
     * until the end of $data is reached. This value does not count the length of the line terminator.
     *
     * @return int The number of bytes written plus the length of the end-of-line sequence.
     * @throws InvalidOperationException If the stream is closed.
     */
    public function writeLine(string $data, int $length = -1): int
    {
        $bytes = $this->write($data, $length);
        $bytes += $this->write(PHP_EOL);
        return $bytes;
    }

    /**
     * Writes the entire contents of this memory stream to another stream.
     *
     * @param Stream $stream The stream to write the contents of this stream to.
     * @param int $buffer_size The size of the buffer. This value must be greater than zero.
     *
     * @throws IOException If this stream is not readable or the destination stream is not writable.
     * @throws InvalidOperationException If the stream is closed.
     * @throws NotSupportedException If the stream is not readable or the destination is not writable.
     */
    public function writeTo(Stream $stream, int $buffer_size = 81920): void
    {
        $this->setPosition(0);
        $this->copyTo($stream, $buffer_size);
    }

    /**
     * Does nothing as the stream is written to the memory.
     */
    public function flush(): void
    {
    }

    /**
     * Closes the stream.
     */
    public function close(): void
    {
        if ($this->memory !== null) {
            fclose($this->memory);
            $this->memory = null;
        }
    }

    /**
     * Throws an exception if the user attempts to do something when the stream is closed.
     *
     * @throws InvalidOperationException
     */
    private function ensureStreamIsOpen(): void
    {
        if ($this->memory === null) {
            throw new InvalidOperationException('The stream is closed');
        }
    }
}
