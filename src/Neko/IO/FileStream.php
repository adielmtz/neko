<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\InvalidOperationException;
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
 * Represents a IO Stream for files.
 */
final class FileStream extends Stream
{
    private string $filename;
    private mixed $handle;
    private bool $can_seek;
    private bool $can_read;
    private bool $can_write;

    /**
     * FileStream constructor.
     *
     * @param string $filename The path to the file to open.
     * @param FileMode $mode
     * @param FileAccess $access
     *
     * @throws InvalidArgumentException if the file path or open mode is an empty string.
     * @throws FileNotFoundException if the file cannot be found or does not exist.
     * @throws IOException If the stream cannot be open.
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
                sprintf('fopen(%s, %s) failed', $filename, $mode)
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
    public function canRead(): bool
    {
        return $this->can_read;
    }

    /**
     * Returns true if the stream is writable.
     *
     * @return bool
     */
    public function canWrite(): bool
    {
        return $this->can_write;
    }

    /**
     * Returns true if the stream is seekable.
     *
     * @return bool
     */
    public function canSeek(): bool
    {
        return $this->can_seek;
    }

    /**
     * Returns the path of the file opened by the stream.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Returns true if the current position in the stream equals the end of file.
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
     * Returns the size of the stream in bytes.
     *
     * @return int
     * @throws IOException if fstat failed.
     * @throws InvalidOperationException if the stream is closed or the stream does not support seeking.
     */
    public function getSize(): int
    {
        $this->ensureStreamIsSeekable();
        $stat = @fstat($this->handle);
        if ($stat === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fstat(%s) failed', $this->filename)
            );
        }

        return $stat['size'];
    }

    /**
     * Sets the size, in bytes, of the stream.
     *
     * @param int $size The new size.
     *
     * @return void
     * @throws IOException
     * @throws InvalidArgumentException if the size is less than zero.
     * @throws InvalidOperationException if the stream is closed or the stream does not support seeking.
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
     * Gets the current position in the stream.
     *
     * @return int
     *
     * @throws InvalidOperationException if the stream is closed or the stream does not support seeking.
     */
    public function getPosition(): int
    {
        $this->ensureStreamIsSeekable();
        return (int) ftell($this->handle);
    }

    /**
     * Sets the current position in the stream to the given value.
     *
     * @param int $position The position in the stream.
     *
     * @return void
     * @throws IOException if fseek failed.
     * @throws InvalidOperationException if the stream is closed or the stream does not support seeking.
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
     * @throws IOException if fseek failed
     * @throws InvalidOperationException if the stream is closed.
     */
    public function seek(int $offset, int $whence): void
    {
        $this->ensureStreamIsSeekable();
        if (@fseek($this->handle, $offset, $whence) === -1) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fseek(%s) failed', $this->filename)
            );
        }
    }

    /**
     * Locks the file using a read/write lock.
     *
     * @param bool $no_block True to avoid blocking while attempting to lock the file.
     *
     * @return bool True if the file was locked successfully; otherwise, false.
     * @throws InvalidOperationException If the stream is closed.
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
     * Releases the read/write lock from the file.
     *
     * @throws InvalidOperationException If the stream is closed.
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
     * Reads a block of bytes from the stream.
     *
     * @param int $length The maximum number of bytes to read.
     *
     * @return string The data read from the stream.
     * @throws IOException if fread failed.
     * @throws InvalidArgumentException if the read length is less than or equal to zero.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
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
                sprintf('fread(%s, %d) failed', $this->filename, $length)
            );
        }

        return $data;
    }

    /**
     * Reads a char from the stream.
     *
     * @return string|null The read character or null if the end of the stream has been reached.
     * @throws InvalidOperationException if the stream is closed or does not support reading.
     */
    public function readChar(): ?string
    {
        $this->ensureStreamIsReadable();
        $c = @fgetc($this->handle);
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
        $this->ensureStreamIsReadable();
        $data = @fgets($this->handle);
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
        $this->ensureStreamIsReadable();
        $data = @stream_get_contents($this->handle);
        return $data === false ? '' : $data;
    }

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to write.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop until
     *     the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws IOException if fwrite failed.
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    public function write(string $data, int $length = -1): int
    {
        $this->ensureStreamIsWritable();
        $length = $length > -1 ? $length : strlen($data);
        $bytes = fwrite($this->handle, $data, $length);

        if ($bytes === false) {
            throw IOException::fromLastErrorOrDefault(
                sprintf('fwrite(%s, ..., %d) failed', $this->filename, $length)
            );
        }

        return $bytes;
    }

    /**
     * Writes a string to the stream, followed by an end-of-line sequence.
     *
     * @param string $data The string to write.
     *
     * @return int The number of bytes written, including the length of the end-of-line sequence.
     * @throws IOException if fwrite failed.
     * @throws InvalidOperationException if the stream is closed or does not support writing.
     */
    public function writeLine(string $data): int
    {
        return $this->write($data . PHP_EOL);
    }

    /**
     * Forces any buffered data to be written into the stream.
     *
     * @return void
     * @throws InvalidOperationException if the stream is closed or does not support writing.
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
