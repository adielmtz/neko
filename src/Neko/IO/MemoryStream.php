<?php declare(strict_types=1);
namespace Neko\IO;

use Neko\InvalidOperationException;
use Neko\NotSupportedException;
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
 * Represents an IO stream in memory.
 */
class MemoryStream extends Stream
{
    private mixed $memory;

    /**
     * MemoryStream constructor.
     *
     * @throws IOException if the stream could not be open.
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
    public function isReadable(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->memory !== null;
    }

    /**
     * Returns true if the current position is at the end of the stream.
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
     * Gets the size of the stream.
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
     * Sets the size of the stream.
     *
     * @param int $size The size of the stream in bytes.
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
     * Gets the current position within the stream.
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
     * Sets the current position within the stream.
     *
     * @param int $position The position offset.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    public function setPosition(int $position): void
    {
        $this->seek($position, SEEK_SET);
    }

    /**
     * Seeks on the stream moving the current position.
     *
     * @param int $offset The seek offset.
     * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
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
     * Reads up to $length bytes from the stream.
     *
     * @param int $length The amount of bytes to read.
     *
     * @return string The data read from the stream or an empty string if the end of stream was reached.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function read(int $length): string
    {
        $this->ensureStreamIsOpen();
        $buffer = fread($this->memory, $length);
        return $buffer === false ? '' : $buffer;
    }

    /**
     * Reads a byte from the stream.
     *
     * @return string|null The read byte or NULL if the end of stream was reached.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function readChar(): ?string
    {
        $this->ensureStreamIsOpen();
        $c = fgetc($this->memory);
        return $c === false ? null : $c;
    }

    /**
     * Reads a line from the stream.
     *
     * @return string|null The data read from the stream or NULL if the end of stream has been reached.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function readLine(): ?string
    {
        $this->ensureStreamIsOpen();
        $data = fgets($this->memory);
        return $data === false ? null : $data;
    }

    /**
     * Reads the stream until it reaches the end of stream.
     *
     * @return string The data read from the stream.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function readToEnd(): string
    {
        $this->ensureStreamIsOpen();
        return stream_get_contents($this->memory);
    }

    /**
     * Writes up to $length bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The amount of bytes to write. If $length is less than zero, the whole string will be written.
     *
     * @return int The number of bytes written.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function write(string $data, int $length = -1): int
    {
        $this->ensureStreamIsOpen();
        $length = $length > -1 ? $length : strlen($data);
        return fwrite($this->memory, $data, $length);
    }

    /**
     * Writes to the stream followed by an end-of-line sequence.
     *
     * @param string $data The data to write.
     *
     * @return int The number of bytes written.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function writeLine(string $data): int
    {
        return $this->write($data . PHP_EOL);
    }

    /**
     * Copies data from the beginning of this stream to another.
     *
     * @param Stream $destination The destination stream where the copied data will be written.
     * @param int $buffer_size The size of the internal buffer.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the underlying stream implementation does not support this operation.
     */
    public function writeTo(Stream $destination, int $buffer_size = 81920): void
    {
        $this->setPosition(0);
        $this->copyTo($destination, $buffer_size);
    }

    /**
     * A no-op since the stream is stored in memory.
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
