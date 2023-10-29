<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use function fclose;
use function feof;
use function fflush;
use function fgetc;
use function fgets;
use function file_exists;
use function flock;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function ftruncate;
use function fwrite;
use function sprintf;
use function stream_get_contents;
use function stream_get_meta_data;
use function strlen;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;
use const PHP_EOL;
use const SEEK_SET;

/**
 * Represents an IO stream for files.
 */
final class FileStream extends Stream
{
    private string $filename;
    private mixed $handle;
    private bool $can_seek;
    private bool $can_read;
    private bool $can_write;
    private bool $is_locked = false;

    /**
     * FileStream constructor.
     *
     * @param string $filename The path to the file to open.
     * @param FileMode $mode The stream open mode.
     * @param FileAccess $access The level of access for the stream.
     *
     * @throws InvalidArgumentException if the file path is an empty string.
     * @throws FileNotFoundException if the file is not found or does not exist.
     * @throws IOException if an IO error occurs.
     */
    public function __construct(string $filename, FileMode $mode, FileAccess $access)
    {
        if ($filename === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        if ($mode === FileMode::Open && !file_exists($filename)) {
            throw new FileNotFoundException("Could not find file '$filename'");
        }

        $mode = $mode->getOpenMode($access);
        $this->handle = @fopen($filename, $mode);
        if ($this->handle === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fopen(%s, %s) failed', $filename, $mode),
            );
        }

        $this->filename = $filename;
        $this->can_seek = stream_get_meta_data($this->handle)['seekable'];
        $this->can_read = $access->canRead();
        $this->can_write = $access->canWrite();
    }

    /**
     * Returns true if the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->can_read;
    }

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->can_write;
    }

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->can_seek;
    }

    /**
     * Gets the path of the file opened by the stream.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
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
        return feof($this->handle);
    }

    /**
     * Gets the size of the stream.
     *
     * @return int
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support seeking.
     */
    public function getSize(): int
    {
        $this->ensureStreamIsSeekable();
        $stat = @fstat($this->handle);
        if ($stat === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fstat(%s) failed', $this->filename),
            );
        }

        return $stat['size'];
    }

    /**
     * Sets the size of the stream.
     *
     * @param int $size The size of the stream in bytes.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support seeking.
     */
    public function setSize(int $size): void
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Stream size must be greater than or equal to 0');
        }

        $this->ensureStreamIsSeekable();
        if (ftruncate($this->handle, $size)) {
            if ($this->getPosition() > $size) {
                $this->seek($size, SEEK_SET);
            }
        }
    }

    /**
     * Gets the current position within the stream.
     *
     * @return int
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support seeking.
     */
    public function getPosition(): int
    {
        $this->ensureStreamIsSeekable();
        return (int) ftell($this->handle);
    }

    /**
     * Sets the current position within the stream.
     *
     * @param int $position The position offset.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support seeking.
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
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support seek.
     */
    public function seek(int $offset, int $whence): void
    {
        $this->ensureStreamIsSeekable();
        if (@fseek($this->handle, $offset, $whence) === -1) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fseek(%s) failed', $this->filename),
            );
        }
    }

    /**
     * Locks the file with an exclusive read/write lock.
     *
     * @param bool $no_block True to avoid blocking while attempting to get a lock on the file.
     *
     * @return bool True if the file was locked successfully; otherwise, false.
     * @throws InvalidOperationException if the stream is closed.
     */
    public function lock(bool $no_block = false): bool
    {
        if (!$this->is_locked) {
            $this->ensureStreamIsOpen();
            $mode = LOCK_EX;
            if ($no_block) {
                $mode |= LOCK_NB;
            }

            $this->is_locked = flock($this->handle, $mode);
        }

        return $this->is_locked;
    }

    /**
     * Releases the lock from the file.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed.
     */
    public function unlock(): void
    {
        if ($this->is_locked) {
            $this->ensureStreamIsOpen();
            flock($this->handle, LOCK_UN);
            $this->is_locked = false;
        }
    }

    /**
     * Reads up to $length bytes from the stream.
     *
     * @param int $length The amount of bytes to read.
     *
     * @return string The data read from the stream or an empty string if the end of stream was reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support read.
     */
    public function read(int $length): string
    {
        $this->ensureStreamIsReadable();
        if ($length <= 0) {
            throw new InvalidArgumentException('Read length must be greater than 0');
        }

        $data = @fread($this->handle, $length);
        if ($data === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fread(%s, %d) failed', $this->filename, $length),
            );
        }

        return $data;
    }

    /**
     * Reads a byte from the stream.
     *
     * @return string|null The read byte or NULL if the end of stream was reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support read.
     */
    public function readChar(): ?string
    {
        $this->ensureStreamIsReadable();
        $c = @fgetc($this->handle);
        return $c === false ? null : $c;
    }

    /**
     * Reads a line from the stream.
     *
     * @return string|null The data read from the stream or NULL if the end of stream has been reached.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support read.
     */
    public function readLine(): ?string
    {
        $this->ensureStreamIsReadable();
        $data = @fgets($this->handle);
        return $data === false ? null : $data;
    }

    /**
     * Reads the stream until it reaches the end of stream.
     *
     * @return string The data read from the stream.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support read.
     */
    public function readToEnd(): string
    {
        $this->ensureStreamIsReadable();
        $data = @stream_get_contents($this->handle);
        return $data === false ? '' : $data;
    }

    /**
     * Writes up to $length bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The amount of bytes to write. If $length is less than zero, the whole string will be written.
     *
     * @return int The number of bytes written.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support write.
     */
    public function write(string $data, int $length = -1): int
    {
        $this->ensureStreamIsWritable();
        $length = $length > -1 ? $length : strlen($data);
        $bytes = fwrite($this->handle, $data, $length);

        if ($bytes === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fwrite(%s, ..., %d) failed', $this->filename, $length),
            );
        }

        return $bytes;
    }

    /**
     * Writes to the stream followed by an end-of-line sequence.
     *
     * @param string $data The data to write.
     *
     * @return int The number of bytes written.
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support write.
     */
    public function writeLine(string $data): int
    {
        return $this->write($data . PHP_EOL);
    }

    /**
     * Forces all buffered data to be written to the underlying stream implementation.
     *
     * @return void
     * @throws IOException if an IO error occurs.
     * @throws InvalidOperationException if the stream is closed.
     * @throws NotSupportedException if the stream does not support write.
     */
    public function flush(): void
    {
        $this->ensureStreamIsWritable();
        fflush($this->handle);
    }

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
            $this->can_seek = false;
            $this->can_read = false;
            $this->can_write = false;
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
        if ($this->handle === null) {
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
        if (!$this->can_seek) {
            throw new InvalidOperationException('Stream does not support seek');
        }
    }
}
