<?php declare(strict_types=1);
namespace Neko\IO;

use Neko\Environment;
use function array_pop;
use function explode;
use function func_num_args;
use function getcwd;
use function implode;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Contains methods for path string manipulation.
 */
final class Path
{
    /**
     * Returns true if the given character is a directory separator.
     *
     * @param string $char The character to test.
     *
     * @return bool
     */
    public static function isDirectorySeparator(string $char): bool
    {
        return $char === DIRECTORY_SEPARATOR || $char === '/';
    }

    /**
     * Returns true if the given path ends in a directory separator character.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function endsInDirectorySeparator(string $path): bool
    {
        $length = strlen($path);
        return $length > 0 && self::isDirectorySeparator($path[$length - 1]);
    }

    /**
     * Returns true if the given path has a root component.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function hasRoot(string $path): bool
    {
        return self::getRootLength($path) > 0;
    }

    /**
     * Returns the root component of the given path.
     *
     * @param string $path The path to process.
     *
     * @return string The root component or an empty string if the path does not contain a root component.
     */
    public static function getRoot(string $path): string
    {
        return self::split($path)[0];
    }

    /**
     * Returns true if the given path is absolute (not necessarily canonical).
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function isAbsolute(string $path): bool
    {
        return $path !== '' && self::hasRoot($path);
    }

    /**
     * Returns true if the given path has a file extension.
     *
     * @param string $path The path to test.
     *
     * @return bool
     */
    public static function hasExtension(string $path): bool
    {
        $length = strlen($path);
        for ($i = $length - 1; $i >= 0; $i--) {
            $c = $path[$i];
            if ($c === '.') {
                return $i !== $length - 1;
            }

            if (self::isDirectorySeparator($c)) {
                break;
            }
        }

        return false;
    }

    /**
     * Returns the file extension (including the leading period) of the given path.
     *
     * @param string $path The path to process.
     *
     * @return string The file extension or an empty string if the path does not contain a file extension component.
     */
    public static function getExtension(string $path): string
    {
        $length = strlen($path);
        for ($i = $length - 1; $i >= 0; $i--) {
            $c = $path[$i];
            if ($c === '.' && $i !== $length - 1) {
                return substr($path, $i);
            }

            if (self::isDirectorySeparator($c)) {
                break;
            }
        }

        return '';
    }

    /**
     * Changes the extension of the given path.
     *
     * @param string $path The path to modify.
     * @param string|null $extension The new extension. The extension can contain a leading period.
     * If NULL is passed, the extension will be removed from the path.
     *
     * @return string The path with the new extension.
     */
    public static function changeExtension(string $path, ?string $extension): string
    {
        $length = strlen($path);
        if ($length === 0) {
            return '';
        }

        for ($i = $length - 1; $i >= 0; $i--) {
            $c = $path[$i];
            if ($c === '.') {
                $length = $i;
                break;
            }

            if (self::isDirectorySeparator($c)) {
                break;
            }
        }

        $trimmedPath = substr($path, 0, $length);
        if ($extension === null) {
            return $trimmedPath;
        }

        if (!str_starts_with($extension, '.')) {
            $extension = '.' . $extension;
        }

        return $trimmedPath . $extension;
    }

    /**
     * Returns the directory name component of the path.
     *
     * @param string $path The path to process.
     *
     * @return string The directory name or an empty string if the given path does not contain a directory component.
     */
    public static function getDirectory(string $path): string
    {
        $rootLength = self::getRootLength($path);
        $end = strlen($path) - 1;

        if ($end < $rootLength) {
            return '';
        }

        while ($end > $rootLength && !self::isDirectorySeparator($path[$end])) {
            $end--;
        }

        while ($end > $rootLength && self::isDirectorySeparator($path[$end - 1])) {
            // trim duplicated directory separators
            // <= "/foo/bar//"
            // => "/foo/bar"
            $end--;
        }

        return substr($path, 0, $end);
    }

    /**
     * Returns the filename component of the path.
     *
     * @param string $path The path to process.
     *
     * @return string The filename or an empty string if the given path does not contain a filename component.
     */
    public static function getFilename(string $path): string
    {
        $rootLength = self::getRootLength($path);
        for ($i = strlen($path) - 1; $i >= 0; $i--) {
            if ($i < $rootLength || self::isDirectorySeparator($path[$i])) {
                return substr($path, $i + 1);
            }
        }

        return $path;
    }

    /**
     * Returns the filename component of the path without the extension.
     *
     * @param string $path The path to process.
     *
     * @return string The filename without the extension or an empty string if the given path does not contain a
     * filename component.
     */
    public static function getFilenameWithoutExtension(string $path): string
    {
        $filename = self::getFilename($path);
        return $filename === '' ? '' : self::changeExtension($filename, null);
    }

    /**
     * Returns the canonical absolute path.
     * Unlike realpath, this method does not check if the path exists.
     *
     * @param string $path The path to process.
     *
     * @return string
     */
    public static function getFullPath(string $path): string
    {
        if (!self::hasRoot($path)) {
            $path = self::combine(getcwd(), $path);
        }

        $result = self::canonicalize($path);
        return $result === '' ? '/' : $result;
    }

    /**
     * Canonicalizes the given path.
     *
     * @param string $path The path to process.
     *
     * @return string
     */
    public static function canonicalize(string $path): string
    {
        if ($path === '') {
            return '';
        }

        [$root, $path] = self::split($path);
        $normalized = str_replace('\\', '/', $path);
        $exploded_path = explode('/', $normalized);
        $segments = [];
        $segment_count = 0;

        foreach ($exploded_path as $str) {
            if ($str === '' || $str === '.') {
                // ignore empty or '.' segments
                continue;
            }

            if ($str === '..') {
                if ($segment_count > 0) {
                    // remove relative segment if is not at the beginning of the path
                    array_pop($segments);
                    $segment_count--;
                    continue;
                }
            }

            $segments[] = $str;
            $segment_count++;
        }

        return $root . implode('/', $segments);
    }

    /**
     * Combines multiple path strings into a single path.
     * If an argument contains a rooted path, any previous path components are ignored and the returned string begins
     * with the rooted path.
     *
     * @param string ...$paths The paths to combine.
     *
     * @return string
     */
    public static function combine(string ...$paths): string
    {
        $numArgs = func_num_args();
        $startIndex = 0;
        $result = '';

        if ($numArgs > 0) {
            for ($i = $numArgs - 1; $i >= 0; $i--) {
                if ($paths[$i] === '') {
                    continue;
                }

                if (self::hasRoot($paths[$i])) {
                    $startIndex = $i;
                    break;
                }
            }

            $result = $paths[$startIndex++];
            for ($i = $startIndex; $i < $numArgs; $i++) {
                $s = $paths[$i];
                if ($s === '') {
                    continue;
                }

                if (!self::endsInDirectorySeparator($result) && !self::isDirectorySeparator($s[0])) {
                    $result .= '/';
                }

                $result .= $s;
            }
        }

        return $result;
    }

    /**
     * Concatenates a list of path strings into a single path.
     * Unlike `Path::combine()` this method does not check for a rooted path.
     *
     * @param string ...$paths The paths to concatenate.
     *
     * @return string
     */
    public static function join(string ...$paths): string
    {
        $result = '';
        $numArgs = func_num_args();
        if ($numArgs > 0) {
            $result = $paths[0];
            for ($i = 1; $i < $numArgs; $i++) {
                $s = $paths[$i];
                if ($s === '') {
                    continue;
                }

                if (!self::endsInDirectorySeparator($result) && !self::isDirectorySeparator($s[0])) {
                    $result .= '/';
                }

                $result .= $s;
            }
        }

        return $result;
    }

    /**
     * Returns the length of the root component of the given path;
     *
     * @param string $path The path to process.
     *
     * @return int
     */
    private static function getRootLength(string $path): int
    {
        // handle schemes like: 'file://', 'ftp://', etc.
        $schemePos = strpos($path, '://');
        if ($schemePos !== false) {
            return $schemePos + 3;
        }

        $length = strlen($path);
        if (Environment::IS_WINDOWS_MACHINE && $length >= 2 && self::isDriveLetter($path[0]) && $path[1] === ':') {
            return $length >= 3 && self::isDirectorySeparator($path[2]) ? 3 : 2;
        }

        return $length > 0 && self::isDirectorySeparator($path[0]) ? 1 : 0;
    }

    /**
     * Splits the path in root and path components.
     *
     * @param string $path The path to split.
     *
     * @return string[] Index 0 contains the root of the path;
     * Index 1 contains the rest of the path without its root.
     */
    private static function split(string $path): array
    {
        if ($path === '') {
            return ['', ''];
        }

        $rootLength = self::getRootLength($path);
        $root = substr($path, 0, $rootLength);
        $path = substr($path, $rootLength);

        if (Environment::IS_WINDOWS_MACHINE && $rootLength > 0) {
            if ($rootLength === 2) {
                // Handle "C:" --> "C:/" for Windows
                $root .= '/';
            } else {
                // Convert '\' --> '/' if necessary
                $root[$rootLength - 1] = '/';
            }
        }

        return [$root, $path];
    }

    /**
     * Returns true if the given character is a valid drive letter for Windows.
     *
     * @param string $c The character to test.
     *
     * @return bool
     */
    private static function isDriveLetter(string $c): bool
    {
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z');
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
