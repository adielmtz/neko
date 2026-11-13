<?php declare(strict_types=1);
namespace Neko\IO\OS\Unix;

use Neko\Environment;
use Neko\IO\OS\AbstractPath;
use Override;
use RuntimeException;
use const PHP_OS_FAMILY;

/**
 * Path rules for Unix-like platforms: '/' is the only directory separator and the only root.
 *
 * @internal
 */
final class PathUnix extends AbstractPath
{
    #[Override]
    protected function getDirectorySeparator(): string
    {
        return '/';
    }

    #[Override]
    protected function isCaseSensitive(): bool
    {
        // APFS and HFS+ are case-insensitive by default
        return PHP_OS_FAMILY !== 'Darwin';
    }

    #[Override]
    protected function getRootLength(string $path): int
    {
        return $path !== '' && $path[0] === '/' ? 1 : 0;
    }

    #[Override]
    public function isDirectorySeparator(string $chr): bool
    {
        return $chr === '/';
    }

    #[Override]
    public function isPathFullyQualified(string $path): bool
    {
        return $this->isPathRooted($path);
    }

    #[Override]
    public function getFullPath(string $path): string
    {
        if (!$this->isPathRooted($path)) {
            $cwd = Environment::getCurrentDirectory()
                ?? throw new RuntimeException('Unable to determine the current working directory');

            $path = $this->combine($cwd, $path);
        }

        return $this->normalize($path);
    }
}
