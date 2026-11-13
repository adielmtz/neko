<?php declare(strict_types=1);
namespace Neko\IO\OS\Windows;

use Neko\Environment;
use Neko\IO\OS\AbstractPath;
use Override;
use RuntimeException;
use function ctype_alpha;
use function str_replace;
use function strcasecmp;
use function strlen;
use function substr;

/**
 * Path rules for Windows: both '\' and '/' are directory separators, and a root can be a drive ("C:\", or the
 * drive-relative "C:"), the root of the current drive ("\"), a UNC share ("\\server\share") or a device path
 * ("\\?\C:\", "\\.\COM1", "\\?\UNC\server\share").
 *
 * @internal
 */
final class PathWindows extends AbstractPath
{
    #[Override]
    protected function getDirectorySeparator(): string
    {
        return '\\';
    }

    #[Override]
    protected function isCaseSensitive(): bool
    {
        return false;
    }

    #[Override]
    protected function getRootLength(string $path): int
    {
        $length = strlen($path);
        if ($length >= 2 && $this->isDirectorySeparator($path[0]) && $this->isDirectorySeparator($path[1])) {
            if ($this->isDevicePath($path)) {
                if ($length >= 8 && strcasecmp(substr($path, 4, 3), 'UNC') === 0 && $this->isDirectorySeparator($path[7])) {
                    // \\?\UNC\server\share
                    return $this->getUncRootLength($path, 8);
                }

                // \\?\C:\, \\.\COM1
                $i = 4;
                while ($i < $length && !$this->isDirectorySeparator($path[$i])) {
                    $i++;
                }

                return $i < $length ? $i + 1 : $length;
            }

            // \\server\share
            return $this->getUncRootLength($path, 2);
        }

        if ($length >= 2 && $path[1] === ':' && ctype_alpha($path[0])) {
            // C:\ or C:
            return $length >= 3 && $this->isDirectorySeparator($path[2]) ? 3 : 2;
        }

        // \
        return $length >= 1 && $this->isDirectorySeparator($path[0]) ? 1 : 0;
    }

    #[Override]
    public function isDirectorySeparator(string $chr): bool
    {
        return $chr === '\\' || $chr === '/';
    }

    #[Override]
    public function isPathFullyQualified(string $path): bool
    {
        if (strlen($path) < 2) {
            return false;
        }

        if ($this->isDirectorySeparator($path[0])) {
            // UNC and device paths are fully qualified, "\foo" is relative to the current drive
            return $this->isDirectorySeparator($path[1]);
        }

        return strlen($path) >= 3 && $path[1] === ':' && ctype_alpha($path[0]) && $this->isDirectorySeparator($path[2]);
    }

    #[Override]
    public function getFullPath(string $path): string
    {
        if ($this->isDevicePath($path)) {
            // device paths are passed to the system as they are
            return $path;
        }

        if (!$this->isPathFullyQualified($path)) {
            $path = $this->resolve($path);
        }

        return $this->normalize($path);
    }

    #[Override]
    protected function normalizeDirectorySeparators(string $path): string
    {
        return str_replace('/', '\\', $path);
    }

    /**
     * Resolves a path that is not fully qualified against the current working directory.
     */
    private function resolve(string $path): string
    {
        $cwd = Environment::getCurrentDirectory()
            ?? throw new RuntimeException('Unable to determine the current working directory');

        $rootLength = $this->getRootLength($path);
        if ($rootLength === 0) {
            // foo\bar
            return $this->combine($cwd, $path);
        }

        if ($rootLength === 1) {
            // \foo\bar is relative to the root of the current drive
            return $this->join($this->getPathRoot($cwd), substr($path, 1));
        }

        // C:foo is relative to the current directory of drive C:, which is only known for the current drive
        $relative = substr($path, 2);
        if (strlen($cwd) >= 2 && $cwd[1] === ':' && strcasecmp($cwd[0], $path[0]) === 0) {
            return $this->combine($cwd, $relative);
        }

        return $path[0] . ':\\' . $relative;
    }

    /**
     * Returns true if the path starts with the device prefix "\\?\" or "\\.\".
     */
    private function isDevicePath(string $path): bool
    {
        return strlen($path) >= 4
            && $this->isDirectorySeparator($path[0])
            && $this->isDirectorySeparator($path[1])
            && ($path[2] === '?' || $path[2] === '.')
            && $this->isDirectorySeparator($path[3]);
    }

    /**
     * Returns the length of a "server\share" root that starts at the given offset.
     */
    private function getUncRootLength(string $path, int $offset): int
    {
        $length = strlen($path);
        $i = $offset;
        while ($i < $length && !$this->isDirectorySeparator($path[$i])) {
            $i++; // server
        }

        if ($i < $length) {
            $i++;
        }

        while ($i < $length && !$this->isDirectorySeparator($path[$i])) {
            $i++; // share
        }

        return $i;
    }
}
