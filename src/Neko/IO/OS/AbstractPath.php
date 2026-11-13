<?php declare(strict_types=1);
namespace Neko\IO\OS;

use function array_fill;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function implode;
use function mb_check_encoding;
use function mb_convert_case;
use function min;
use function str_starts_with;
use function strcasecmp;
use function strlen;
use function substr;
use const MB_CASE_UPPER_SIMPLE;

/**
 * Base implementation of the path operations exposed by {@see \Neko\IO\Path}.
 * Subclasses provide the platform-specific rules, such as the directory separators and the structure of a root.
 *
 * @internal
 */
abstract class AbstractPath
{
    /**
     * Returns the character used to separate directory levels in normalized paths.
     */
    protected abstract function getDirectorySeparator(): string;

    /**
     * Returns true if the file system compares paths in a case-sensitive way.
     */
    protected abstract function isCaseSensitive(): bool;

    /**
     * Returns the length of the root component of the path, or 0 if the path is not rooted.
     */
    protected abstract function getRootLength(string $path): int;

    public abstract function isDirectorySeparator(string $chr): bool;

    public abstract function isPathFullyQualified(string $path): bool;

    public abstract function getFullPath(string $path): string;

    public function endsInDirectorySeparator(string $path): bool
    {
        return $path !== '' && $this->isDirectorySeparator($path[-1]);
    }

    public function isPathRooted(string $path): bool
    {
        return $this->getRootLength($path) > 0;
    }

    public function getPathRoot(string $path): string
    {
        return substr($path, 0, $this->getRootLength($path));
    }

    public function hasExtension(string $path): bool
    {
        return $this->getExtension($path) !== '';
    }

    public function getExtension(string $path): string
    {
        $length = strlen($path);
        for ($i = $length - 1; $i >= 0; $i--) {
            $c = $path[$i];
            if ($c === '.') {
                return $i === $length - 1 ? '' : substr($path, $i);
            }

            if ($this->isDirectorySeparator($c)) {
                break;
            }
        }

        return '';
    }

    public function changeExtension(string $path, ?string $extension): string
    {
        if ($path === '') {
            return '';
        }

        $end = strlen($path);
        for ($i = $end - 1; $i >= 0; $i--) {
            $c = $path[$i];
            if ($c === '.') {
                $end = $i;
                break;
            }

            if ($this->isDirectorySeparator($c)) {
                break;
            }
        }

        $result = substr($path, 0, $end);
        if ($extension === null) {
            return $result;
        }

        if (!str_starts_with($extension, '.')) {
            $extension = '.' . $extension;
        }

        return $result . $extension;
    }

    public function getFileName(string $path): string
    {
        $rootLength = $this->getRootLength($path);
        for ($i = strlen($path) - 1; $i >= $rootLength; $i--) {
            if ($this->isDirectorySeparator($path[$i])) {
                return substr($path, $i + 1);
            }
        }

        return substr($path, $rootLength);
    }

    public function getFileNameWithoutExtension(string $path): string
    {
        return $this->changeExtension($this->getFileName($path), null);
    }

    public function getDirectoryName(string $path): string
    {
        $rootLength = $this->getRootLength($path);
        if (strlen($path) <= $rootLength) {
            return '';
        }

        // find the last directory separator after the root
        $end = strlen($path) - 1;
        while ($end >= $rootLength && !$this->isDirectorySeparator($path[$end])) {
            $end--;
        }

        if ($end < $rootLength) {
            return substr($path, 0, $rootLength);
        }

        // skip consecutive directory separators
        while ($end > $rootLength && $this->isDirectorySeparator($path[$end - 1])) {
            $end--;
        }

        return substr($path, 0, $end);
    }

    public function getRelativePath(string $relativeTo, string $path): string
    {
        $from = $this->getFullPath($relativeTo);
        $to = $this->getFullPath($path);

        $rootLength = $this->getRootLength($from);
        if (!$this->equals(substr($from, 0, $rootLength), $this->getPathRoot($to))) {
            return $to;
        }

        $fromSegments = $this->getSegments(substr($from, $rootLength));
        $toSegments = $this->getSegments(substr($to, $rootLength));
        $fromCount = count($fromSegments);
        $toCount = count($toSegments);

        $common = 0;
        $max = min($fromCount, $toCount);
        while ($common < $max && $this->equals($fromSegments[$common], $toSegments[$common])) {
            $common++;
        }

        $segments = array_merge(
            array_fill(0, $fromCount - $common, '..'),
            array_slice($toSegments, $common),
        );

        if ($segments === []) {
            return '.';
        }

        $result = implode($this->getDirectorySeparator(), $segments);
        if ($toCount > $common && $this->endsInDirectorySeparator($to)) {
            $result .= $this->getDirectorySeparator();
        }

        return $result;
    }

    public function combine(string ...$paths): string
    {
        $paths = array_values($paths);
        for ($i = count($paths) - 1; $i > 0; $i--) {
            if ($this->isPathRooted($paths[$i])) {
                return $this->join(...array_slice($paths, $i));
            }
        }

        return $this->join(...$paths);
    }

    public function join(string ...$paths): string
    {
        $result = '';
        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            if ($result !== '' && !$this->endsInDirectorySeparator($result) && !$this->isDirectorySeparator($path[0])) {
                $result .= $this->getDirectorySeparator();
            }

            $result .= $path;
        }

        return $result;
    }

    /**
     * Replaces every alternative directory separator in the path with the primary directory separator.
     */
    protected function normalizeDirectorySeparators(string $path): string
    {
        return $path;
    }

    /**
     * Normalizes the separators of a rooted path and resolves its '.' and '..' segments.
     * A '..' segment never goes above the root of the path.
     */
    protected function normalize(string $path): string
    {
        $rootLength = $this->getRootLength($path);
        $root = $this->normalizeDirectorySeparators(substr($path, 0, $rootLength));

        $segments = [];
        foreach ($this->getSegments(substr($path, $rootLength)) as $segment) {
            if ($segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            return $root;
        }

        $separator = $this->getDirectorySeparator();
        $result = $root;
        if ($result !== '' && !$this->endsInDirectorySeparator($result)) {
            $result .= $separator;
        }

        $result .= implode($separator, $segments);
        if ($this->endsInDirectorySeparator($path)) {
            $result .= $separator;
        }

        return $result;
    }

    /**
     * Splits a path without root into its non-empty segments.
     *
     * @return string[]
     */
    private function getSegments(string $path): array
    {
        $segments = [];
        foreach (explode($this->getDirectorySeparator(), $this->normalizeDirectorySeparators($path)) as $segment) {
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /**
     * Compares two path components according to the case sensitivity of the platform.
     *
     * Case-insensitive file systems (NTFS, APFS) map each character to a single uppercase character, so the simple
     * case mapping is used: "é" matches "É", but "ß" does not match "SS". Strings that are not valid UTF-8 fall back
     * to an ASCII-only comparison, since mbstring would replace the invalid bytes and make different names equal.
     */
    private function equals(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        if ($this->isCaseSensitive()) {
            return false;
        }

        if (!mb_check_encoding($a, 'UTF-8') || !mb_check_encoding($b, 'UTF-8')) {
            return strcasecmp($a, $b) === 0;
        }

        return mb_convert_case($a, MB_CASE_UPPER_SIMPLE, 'UTF-8') === mb_convert_case($b, MB_CASE_UPPER_SIMPLE, 'UTF-8');
    }
}
