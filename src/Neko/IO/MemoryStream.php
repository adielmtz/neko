<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\InvalidOperationException;
use function fclose;
use function feof;
use function fgetc;
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
use const PHP_EOL;
use const SEEK_SET;

/**
 * Represents a stream in memory.
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
        $this->memory = @fopen('php://memory', 'r+b');
        if ($this->memory === false) {
            throw IOException::fromLastErrorOrDefault('fopen(php://memory, r+b) failed');
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
     * Returns true if the current position in the stream equals the size of the stream.
     *
     * @return bool
     * @throws InvalidOperationException if the stream is closed.
     */
    public function endOfStream(): bool
    {
        $this->ensureStreamIsOpen();
        return feof($this->memory);
    }

    /**
     * Returns the size of the stream in bytes.
     *
     * @return int
     * @throws InvalidOperationException if the stream is closed.
     */
    public function getSize(): int
    {
        $this->ensureStreamIsOpen();
        return fstat($this->memory)['size'];
    }

    /**
     * Sets the size, in bytes, of the stream.
     *
     * @param int $size The new size.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    public function setSize(int $size): void
    {
        $this->ensureStreamIsOpen();
        if (ftruncate($this->memory, $size)) {
            if ($this->getPosition() > $size) {
                $this->seek($size, SEEK_SET);
            }
        }
    }

    /**
     * Gets the current position in the stream.
     *
     * @return int
     * @throws InvalidOperationException if the stream is closed.
     */
    public function getPosition(): int
    {
        $this->ensureStreamIsOpen();
        return ftell($this->memory);
    }

    /**
     * Sets the current position in the stream to the given value.
     *
     * @param int $position The position in the stream.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    public function setPosition(int $position): void
    {
        $this->seek($position, SEEK_SET);
    }

    /**
     * Sets the current position in the stream, relative to $whence.
     *
     * @param int $offset The position relative to $whence from which to begin seeking.
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    public function seek(int $offset, int $whence): void
    {
        $this->ensureStreamIsOpen();
        fseek($this->memory, $offset, $whence);
    }

    /**
     * Reads a block of bytes from the stream into the $output argument.
     *
     * @param string|null $output The data read from the stream.
     * @param int $length The maximum number of bytes to read.
     *
     * @return int The number of bytes read.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    public function read(?string &$output, int $length): int
    {
        $this->ensureStreamIsOpen();
        $buffer = fread($this->memory, $length);
        if ($buffer === false) {
            $output = '';
            return 0;
        }

        $output = $buffer;
        return strlen($buffer);
    }

    /**
     * Reads a char from the stream.
     *
     * @return string|null The read character or null if the end of the stream has been reached.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    public function readChar(): ?string
    {
        $this->ensureStreamIsOpen();
        $c = fgetc($this->memory);
        return $c === false ? null : $c;
    }

    /**
     * Reads the stream until an end-of-line sequence is found.
     *
     * @return string|null The data read from the stream or null if the end of the stream has been reached.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    public function readLine(): ?string
    {
        $this->ensureStreamIsOpen();
        $data = fgets($this->memory);
        return $data === false ? null : $data;
    }

    /**
     * Reads the remainder of the stream into a string.
     *
     * @return string The data read from the stream.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    public function readToEnd(): string
    {
        $this->ensureStreamIsOpen();
        return stream_get_contents($this->memory);
    }

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop until
     *     the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws InvalidOperationException if the stream is closed or does not support writing.
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
     * @param string $data The string to write.
     *
     * @return int The number of bytes written, including the length of the end-of-line sequence.
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    public function writeLine(string $data): int
    {
        return $this->write($data . PHP_EOL);
    }

    /**
     * Reads the entire content of the stream and writes it to another stream.
     * In contrast to Stream::copyTo(), this method places the cursor at the beginning of the stream before starting
     * the copy.
     *
     * @param Stream $destination The stream where the copy will be written.
     * @param int $buffer_size The size of the copy buffer.
     *
     * @return void
     * @throws IOException
     * @throws InvalidArgumentException if the buffer size is less than or equal to zero.
     * @throws InvalidOperationException if the stream is not readable or the destination stream is not writable.
     */
    public function writeTo(Stream $destination, int $buffer_size = 81920): void
    {
        $this->setPosition(0);
        $this->copyTo($destination, $buffer_size);
    }

    /**
     * Does nothing as the stream is stored in memory.
     *
     * @return void
     */
    public function flush(): void
    {
    }

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->memory !== null) {
            fclose($this->memory);
            $this->memory = null;
        }
    }

    /**
     * Ensures that the stream is open before attempting to execute any operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    protected function ensureStreamIsOpen(): void
    {
        if ($this->memory === null) {
            throw new InvalidOperationException('Stream is closed');
        }
    }

    /**
     * Ensures that the stream is open and is seekable before attempting to execute any seek operation.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed or does not support seeking.
     */
    protected function ensureStreamIsSeekable(): void
    {
        $this->ensureStreamIsOpen();
    }
}
