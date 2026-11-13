<?php declare(strict_types=1);
namespace Neko\IO;

use Neko\Environment;
use Neko\IO\OS\AbstractPath;
use Neko\IO\OS\Unix\PathUnix;
use Neko\IO\OS\Windows\PathWindows;
use RuntimeException;

/**
 * Contains methods for path string manipulation.
 *
 * The rules depend on the current platform. On Windows, both '\' and '/' are directory separators, roots can be drives
 * ("C:\"), UNC shares ("\\server\share") or device paths ("\\?\C:\"), and paths are compared case-insensitively.
 * On Unix-like platforms, '/' is the only directory separator and root.
 */
final class Path
{
    private static ?AbstractPath $impl = null;

    /**
     * Selects the path implementation for the current platform.
     *
     * This method is called automatically when the class is loaded and does not need to be called manually.
     * Subsequent calls have no effect.
     *
     * @internal
     */
    public static function initialize(): void
    {
        static $initialized = false;
        if ($initialized) {
            return;
        }

        if (Environment::IS_WINDOWS_MACHINE) {
            self::$impl = new PathWindows();
        } else {
            self::$impl = new PathUnix();
        }

        $initialized = true;
    }

    /**
     * Returns true if the given character is a directory separator.
     *
     * @param string $chr The character to test.
     *
     * @return bool
     */
    public static function isDirectorySeparator(string $chr): bool
    {
        return self::$impl->isDirectorySeparator($chr);
    }

    /**
     * Returns true if the given path ends in a directory separator.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function endsInDirectorySeparator(string $path): bool
    {
        return self::$impl->endsInDirectorySeparator($path);
    }

    /**
     * Returns true if the given path contains a root, such as "/", "C:\", "C:" or "\\server\share".
     * A rooted path may still be relative to the current drive or directory; see {@see Path::isPathFullyQualified()}.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function isPathRooted(string $path): bool
    {
        return self::$impl->isPathRooted($path);
    }

    /**
     * Returns true if the given path is fixed to a specific drive or UNC share, and therefore does not depend on the
     * current drive or working directory. On Windows, "C:foo" and "\foo" are rooted but not fully qualified.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function isPathFullyQualified(string $path): bool
    {
        return self::$impl->isPathFullyQualified($path);
    }

    /**
     * Returns the root component of the given path.
     *
     * @param string $path The path to process.
     *
     * @return string The root as it appears in the path, or an empty string if the path is not rooted.
     */
    public static function getPathRoot(string $path): string
    {
        return self::$impl->getPathRoot($path);
    }

    /**
     * Returns true if the file name of the given path has an extension.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function hasExtension(string $path): bool
    {
        return self::$impl->hasExtension($path);
    }

    /**
     * Returns the extension of the file name of the given path, including the leading period.
     *
     * @param string $path The path to process.
     *
     * @return string The extension, or an empty string if the file name has no extension or ends with a period.
     */
    public static function getExtension(string $path): string
    {
        return self::$impl->getExtension($path);
    }

    /**
     * Changes the extension of the file name of the given path.
     *
     * @param string $path The path to modify.
     * @param string|null $extension The new extension, with or without a leading period;
     * or null to remove the extension.
     *
     * @return string The path with the new extension, or an empty string if the path is empty.
     */
    public static function changeExtension(string $path, ?string $extension): string
    {
        return self::$impl->changeExtension($path, $extension);
    }

    /**
     * Returns the file name component of the given path, which is everything after the last directory separator.
     *
     * @param string $path The path to process.
     *
     * @return string The file name, or an empty string if the path ends in a directory separator or is a root.
     */
    public static function getFileName(string $path): string
    {
        return self::$impl->getFileName($path);
    }

    /**
     * Returns the file name component of the given path without its extension.
     *
     * @param string $path The path to process.
     *
     * @return string The file name without its extension, or an empty string if there is no file name.
     */
    public static function getFileNameWithoutExtension(string $path): string
    {
        return self::$impl->getFileNameWithoutExtension($path);
    }

    /**
     * Returns the directory component of the given path, which is everything before the last directory separator.
     * Trailing directory separators are removed, unless the directory is the root.
     *
     * @param string $path The path to process.
     *
     * @return string The directory, or an empty string if the path has no directory component or is a root.
     */
    public static function getDirectoryName(string $path): string
    {
        return self::$impl->getDirectoryName($path);
    }

    /**
     * Returns the absolute path of the given path. Relative paths are resolved against the current working
     * directory, '.' and '..' segments are resolved, and directory separators are normalized.
     * Unlike realpath(), the path does not need to exist and symbolic links are not resolved.
     *
     * @param string $path The path to process.
     *
     * @return string The fully qualified path.
     * @throws RuntimeException If the path is relative and the current working directory cannot be determined.
     */
    public static function getFullPath(string $path): string
    {
        return self::$impl->getFullPath($path);
    }

    /**
     * Returns the path of one path relative to another.
     *
     * @param string $relativeTo The directory the result is relative to.
     * @param string $path The path to make relative.
     *
     * @return string The relative path; "." if both paths are the same directory; or the full path of $path if the
     * paths do not share the same root.
     * @throws RuntimeException If a path is relative and the current working directory cannot be determined.
     */
    public static function getRelativePath(string $relativeTo, string $path): string
    {
        return self::$impl->getRelativePath($relativeTo, $path);
    }

    /**
     * Combines the given paths into a single path, adding directory separators where needed.
     * If an argument is rooted, all the previous arguments are discarded. Empty arguments are ignored.
     *
     * @param string ...$paths The paths to combine.
     *
     * @return string
     */
    public static function combine(string ...$paths): string
    {
        return self::$impl->combine(...$paths);
    }

    /**
     * Concatenates the given paths into a single path, adding directory separators where needed.
     * Unlike {@see Path::combine()}, rooted arguments do not discard the previous ones. Empty arguments are ignored.
     *
     * @param string ...$paths The paths to join.
     *
     * @return string
     */
    public static function join(string ...$paths): string
    {
        return self::$impl->join(...$paths);
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}

/**
 * Self-initialize.
 */
Path::initialize();
