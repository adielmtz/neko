<?php declare(strict_types=1);
namespace Neko\IO;

/**
 * Represents a no-op stream.
 */
final class NullStream extends Stream
{
    public function canRead(): bool
    {
        return true;
    }

    public function canWrite(): bool
    {
        return true;
    }

    public function canSeek(): bool
    {
        return true;
    }

    public function endOfStream(): bool
    {
        return true;
    }

    public function getSize(): int
    {
        return 0;
    }

    public function setSize(int $size): void
    {
    }

    public function getPosition(): int
    {
        return 0;
    }

    public function setPosition(int $position): void
    {
    }

    public function seek(int $offset, int $whence): void
    {
    }

    public function read(?string &$output, int $length): int
    {
        $output = '';
        return 0;
    }

    public function readChar(): ?string
    {
        return null;
    }

    public function readLine(): string
    {
        return '';
    }

    public function readToEnd(): string
    {
        return '';
    }

    public function write(string $data, int $length = -1): int
    {
        return 0;
    }

    public function writeLine(string $data): int
    {
        return 0;
    }

    public function flush(): void
    {
    }

    public function close(): void
    {
    }

    protected function ensureStreamIsOpen(): void
    {
    }

    protected function ensureStreamIsReadable(): void
    {
    }

    protected function ensureStreamIsWritable(): void
    {
    }

    protected function ensureStreamIsSeekable(): void
    {
    }
}
