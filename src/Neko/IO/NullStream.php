<?php declare(strict_types=1);
namespace Neko\IO;

use Override;

/**
 * Represents a no-op stream.
 */
final class NullStream extends Stream
{
    #[Override]
    public function isReadable(): bool
    {
        return true;
    }

    #[Override]
    public function isWritable(): bool
    {
        return true;
    }

    #[Override]
    public function isSeekable(): bool
    {
        return true;
    }

    #[Override]
    public function endOfStream(): bool
    {
        return true;
    }

    #[Override]
    public function getSize(): int
    {
        return 0;
    }

    #[Override]
    public function setSize(int $size): void
    {
    }

    #[Override]
    public function getPosition(): int
    {
        return 0;
    }

    #[Override]
    public function setPosition(int $position): void
    {
    }

    #[Override]
    public function seek(int $offset, int $whence): void
    {
    }

    #[Override]
    public function read(int $length): string
    {
        return '';
    }

    #[Override]
    public function readChar(): ?string
    {
        return null;
    }

    #[Override]
    public function readLine(): string
    {
        return '';
    }

    #[Override]
    public function readToEnd(): string
    {
        return '';
    }

    #[Override]
    public function write(string $data, int $length = -1): int
    {
        return 0;
    }

    #[Override]
    public function writeLine(string $data): int
    {
        return 0;
    }

    #[Override]
    public function flush(): void
    {
    }

    #[Override]
    public function close(): void
    {
    }

    #[Override]
    protected function ensureStreamIsOpen(): void
    {
    }

    #[Override]
    protected function ensureStreamIsReadable(): void
    {
    }

    #[Override]
    protected function ensureStreamIsWritable(): void
    {
    }

    #[Override]
    protected function ensureStreamIsSeekable(): void
    {
    }
}
