<?php declare(strict_types=1);
namespace Neko\IO;

use InvalidArgumentException;
use Neko\InvalidOperationException;
use function fclose;
use function feof;
use function fgetc;
use function fgets;
use function flock;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function ftruncate;
use function fwrite;
use function str_contains;
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
    private mixed $handle;
    private string $file;
    private bool $is_locked = false;
    private bool $can_seek = false;
    private bool $can_read = false;
    private bool $can_write = false;

    /**
     * FileStream constructor.
     *
     * @param string $file The path to the file to open.
     * @param string $mode The open mode (see fopen documentation).
     *
     * @throws IOException If the stream cannot be open.
     * @throws FileNotFoundException If the file doesn't exist or cannot be found.
     */
    public function __construct(string $file, string $mode)
    {
        if ($file === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        if ($mode === '') {
            throw new InvalidArgumentException('Open mode cannot be empty');
        }

        switch ($mode[0]) {
            case 'r':
                $this->can_read = true;
                $this->can_write = str_contains($mode, '+');
                if (!file_exists($file)) {
                    throw new FileNotFoundException("Cannot find file $file");
                }
                break;
            case 'w':
            case 'a':
            case 'x':
            case 'c':
                $this->can_read = str_contains($mode, '+');
                $this->can_write = true;
                break;
            default:
                throw new InvalidArgumentException("Mode '$mode' is not a valid mode");
        }

        $this->handle = @fopen($file, $mode);
        if ($this->handle === false) {
            IOException::throwFromLastError();
        }

        $this->file = $file;
        $this->can_seek = stream_get_meta_data($this->handle)['seekable'];
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
     * Returns the file name from the stream.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->file;
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
        return feof($this->handle);
    }

    /**
     * Gets the stream size in bytes.
     *
     * @return int
     * @throws IOException If fstat failed.
     * @throws InvalidOperationException If the stream is closed or the stream is not readable.
     */
    public function getSize(): int
    {
        $this->ensureStreamIsReadable();
        $stat = @fstat($this->handle);
        if ($stat === false) {
            throw new IOException('stat failed: ' . $this->file);
        }

        return $stat['size'];
    }

    /**
     * Sets the size of the stream.
     *
     * @param int $size The new size. If the new size is less than the current size, the stream will be truncated.
     *
     * @throws IOException
     * @throws InvalidOperationException If the stream is closed or the stream is not writable.
     */
    public function setSize(int $size): void
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Size must be greater than or equal to 0');
        }

        $this->ensureStreamIsWritable();
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
     * @throws InvalidOperationException If the stream is closed or the stream is not seekable.
     */
    public function getPosition(): int
    {
        $this->ensureStreamIsSeekable();
        return (int) ftell($this->handle);
    }

    /**
     * Sets the position in the stream.
     *
     * @param int $position The new position
     *
     * @throws IOException If fseek failed.
     * @throws InvalidOperationException If the stream is closed or the stream is not seekable.
     */
    public function setPosition(int $position): void
    {
        $this->seek($position, SEEK_SET);
    }

    /**
     * Locks the file read and write operations.
     *
     * @param bool $no_block True to avoid blocking while attempting to lock the file.
     *
     * @return bool True if the file was locked; False otherwise.
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
     * Unlocks the file.
     *
     * @throws InvalidOperationException If the stream is closed.
     */
    public function unlock(): void
    {
        if ($this->is_locked) {
            $this->ensureStreamIsOpen();
            flock($this->handle, LOCK_UN);
        }
    }

    /**
     * Seeks on the stream.
     *
     * @param int $offset The new position within the stream, relative to $whence value.
     * @param int $whence The seek reference point.
     *
     * @throws IOException If fseek failed.
     * @throws InvalidOperationException If the stream is closed or the stream is not seekable.
     */
    public function seek(int $offset, int $whence): void
    {
        $this->ensureStreamIsSeekable();
        if (fseek($this->handle, $offset, $whence) === -1) {
            throw new IOException('failed to seek: ' . $this->file);
        }
    }

    /**
     * Reads a block of bytes from the stream.
     *
     * @param string|null $output The data read from the stream.
     * @param int $length The maximum number of bytes to read. This value must be greater than zero.
     *
     * @return int The number of bytes read.
     * @throws InvalidArgumentException If $length is less than or equal to zero.
     * @throws InvalidOperationException If the stream is closed or the stream is not readable.
     */
    public function read(?string &$output, int $length): int
    {
        $this->ensureStreamIsReadable();
        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than 0');
        }

        $data = fread($this->handle, $length);
        if ($data === false) {
            $output = '';
            return 0;
        }

        $output = $data;
        return strlen($data);
    }

    /**
     * Reads a char from the stream.
     *
     * @return string|null The read char or NULL if the end of the stream has been reached.
     * @throws InvalidOperationException If the stream is closed or the stream is not readable.
     */
    public function readChar(): ?string
    {
        $this->ensureStreamIsReadable();
        $c = fgetc($this->handle);
        return $c === false ? null : $c;
    }

    /**
     * Reads the stream until it finds an end-of-line sequence.
     *
     * @return string|null The data read from the stream or NULL if the end of the stream has been reached.
     * @throws InvalidOperationException If the stream is closed or stream is not readable.
     */
    public function readLine(): ?string
    {
        $this->ensureStreamIsReadable();
        $data = fgets($this->handle);
        return $data === false ? null : $data;
    }

    /**
     * Read the entire content of the stream to the end.
     *
     * @return string A string containing the read data.
     * @throws InvalidOperationException If the stream is closed or the stream is not readable.
     */
    public function readToEnd(): string
    {
        $this->ensureStreamIsReadable();
        $data = stream_get_contents($this->handle);
        return $data === false ? '' : $data;
    }

    /**
     * Writes a block of bytes to the stream.
     *
     * @param string $data The data to be written.
     * @param int $length The maximum number of bytes to write. If the value is less than zero, writing will stop
     * until the end of $data is reached.
     *
     * @return int The number of bytes written.
     * @throws IOException If fwrite failed.
     * @throws InvalidOperationException If the stream is closed or the stream is not writable.
     */
    public function write(string $data, int $length = -1): int
    {
        $this->ensureStreamIsWritable();
        $length = $length > -1 ? $length : strlen($data);
        $bytes = fwrite($this->handle, $data, $length);

        if ($bytes === false) {
            throw new IOException('fwrite failed: ' . $this->file);
        }

        return $bytes;
    }

    /**
     * Writes string to the stream, followed by an end-of-line sequence.
     *
     * @param string $data The string to be written.
     *
     * @return int
     * @throws IOException If fwrite failed.
     * @throws InvalidOperationException If the stream is closed or the stream is not writable.
     */
    public function writeLine(string $data): int
    {
        return $this->write($data . PHP_EOL);
    }

    /**
     * Forces any buffered data to be written to the file.
     *
     * @throws InvalidOperationException If the stream is closed or the stream is not writable.
     */
    public function flush(): void
    {
        $this->ensureStreamIsWritable();
        fflush($this->handle);
    }

    /**
     * Closes the stream.
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
     * Throws an InvalidOperationException if the stream is closed.
     *
     * @throws InvalidOperationException
     */
    protected function ensureStreamIsOpen(): void
    {
        if ($this->handle === null) {
            throw new InvalidOperationException('Stream is closed');
        }
    }

    /**
     * Throws NotSupportedException if the stream is not seekable.
     *
     * @throws InvalidOperationException
     */
    private function ensureStreamIsSeekable(): void
    {
        $this->ensureStreamIsOpen();
        if (!$this->can_seek) {
            throw new InvalidOperationException('Stream does not support seek');
        }
    }
}
