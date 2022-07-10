<?php declare(strict_types=1);
namespace Neko;

use function getenv;
use function preg_replace_callback;
use const DIRECTORY_SEPARATOR;

/**
 * Provides information about the current system environment.
 */
final class Environment
{
    public const IS_WINDOWS_MACHINE = DIRECTORY_SEPARATOR === '\\';
    public const IS_UNIX_MACHINE = DIRECTORY_SEPARATOR === '/';

    /**
     * Returns the value of the environment variable or NULL if the variable does not exist.
     *
     * @param string $name The name of the environment variable.
     *
     * @return string|null
     */
    public static function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);
        return $value === false ? null : $value;
    }

    /**
     * Returns the home directory path.
     *
     * @return string
     * @throws NotSupportedException If the home directory path cannot be found.
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
     * Expands environment variables in the string.
     * The variables in the string must be Unix-style variables.
     *
     * @param string $str The string to process.
     *
     * @return string
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
