<?php declare(strict_types=1);
namespace Neko;

use function getenv;
use function preg_replace_callback;
use const DIRECTORY_SEPARATOR;

/**
 * Provides information about the current system and environment.
 */
final class Environment
{
    public const IS_WINDOWS_MACHINE = DIRECTORY_SEPARATOR === '\\';
    public const IS_UNIX_MACHINE = DIRECTORY_SEPARATOR === '/';

    /**
     * Gets the value of the environment variable.
     *
     * @param string $name The name of the environment variable.
     *
     * @return string|null The value of the environment variable or NULL if it was not found.
     */
    public static function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);
        return $value === false ? null : $value;
    }

    /**
     * Gets the home directory path.
     *
     * @return string
     * @throws NotSupportedException if the home directory path cannot be retrieved.
     */
    public static function getHomeDirectory(): string
    {
        $home = getenv('HOME');
        if ($home !== false) {
            return $home;
        }

        $drive = getenv('HOMEDRIVE');
        $path = getenv('HOMEPATH');
        if ($drive !== false && $path !== false) {
            return $drive . $path;
        }

        throw new NotSupportedException('Could not find home directory path');
    }

    /**
     * Expands the name of each environment variable in the given string.
     * The variables in the string are expected to be in unix-style $VAR.
     *
     * @param string $str The string to expand.
     *
     * @return string The expanded string.
     */
    public static function expandEnvironmentVariables(string $str): string
    {
        return preg_replace_callback('/\$(\w+)/', function (array $matches): string {
            $value = self::getEnvironmentVariable($matches[1]);
            return $value === null ? $matches[0] : $value;
        }, $str);
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
