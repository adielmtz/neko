<?php declare(strict_types=1);
namespace Neko;

use RuntimeException;
use function array_filter;
use function function_exists;
use function getcwd;
use function getenv;
use function posix_geteuid;
use function posix_getpwuid;
use function preg_last_error_msg;
use function preg_replace_callback;
use const PHP_OS_FAMILY;
use const PREG_UNMATCHED_AS_NULL;

/**
 * Provides information about the current platform and process environment.
 */
final class Environment
{
    /**
     * Indicates whether the current platform is Windows.
     */
    public const bool IS_WINDOWS = PHP_OS_FAMILY === 'Windows';

    /**
     * Indicates whether the current platform is Unix-like, such as Linux, macOS, BSD or Solaris.
     */
    public const bool IS_UNIX = PHP_OS_FAMILY === 'Linux'
        || PHP_OS_FAMILY === 'Darwin'
        || PHP_OS_FAMILY === 'BSD'
        || PHP_OS_FAMILY === 'Solaris';

    /**
     * Returns the value of the specified environment variable.
     * A variable that is defined with an empty value is treated as undefined.
     *
     * @param string $name The name of the environment variable.
     * @param string|null $fallback The value to return if the variable is undefined or empty.
     *
     * @return string|null The environment variable value, or the fallback if it is undefined or empty.
     */
    public static function get(string $name, ?string $fallback = null): ?string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $fallback;
        }

        return $value;
    }

    /**
     * Returns all environment variables of the current process.
     * Variables that are defined with an empty value are omitted.
     *
     * @return array<string, string> An array that maps the name of each environment variable to its value.
     */
    public static function getAll(): array
    {
        return array_filter(getenv(), static fn(string $value): bool => $value !== '');
    }

    /**
     * Returns the path of the current working directory.
     *
     * @return string|null The path of the current working directory, or null if it cannot be determined, such as
     * when the directory has been deleted or one of its parents is not readable.
     */
    public static function getCurrentDirectory(): ?string
    {
        $path = getcwd();
        return $path === false ? null : $path;
    }

    /**
     * Returns the path of the current user's home directory.
     *
     * On Windows, USERPROFILE is checked first, followed by HOMEDRIVE and HOMEPATH, and finally HOME.
     * On other platforms, HOME is checked first, followed by the user database if the POSIX extension is available.
     *
     * @return string|null The path of the home directory, or null if it cannot be determined.
     */
    public static function getHomeDirectory(): ?string
    {
        if (self::IS_WINDOWS) {
            $profile = self::get('USERPROFILE');
            if ($profile !== null) {
                return $profile;
            }

            $drive = self::get('HOMEDRIVE');
            $path = self::get('HOMEPATH');
            if ($drive !== null && $path !== null) {
                return $drive . $path;
            }

            return self::get('HOME');
        }

        $home = self::get('HOME');
        if ($home !== null) {
            return $home;
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $user = posix_getpwuid(posix_geteuid());
            if ($user !== false && isset($user['dir']) && $user['dir'] !== '') {
                return $user['dir'];
            }
        }

        return null;
    }

    /**
     * Replaces each environment variable reference in a string with the value of that variable.
     *
     * References use the Unix forms $NAME and ${NAME}, where NAME starts with a letter or an underscore, followed by
     * letters, digits or underscores. References to variables that are not defined or are empty are left unchanged.
     *
     * @param string $str The string containing the references to expand.
     *
     * @return string The string with each reference replaced by the value of its variable.
     * @throws RuntimeException If the string cannot be processed by the regular expression engine.
     */
    public static function expand(string $str): string
    {
        return preg_replace_callback(
            '/\$(?:\{([A-Za-z_]\w*)\}|([A-Za-z_]\w*))/',
            static fn(array $matches): string => self::get($matches[1] ?? $matches[2]) ?? $matches[0],
            $str,
            flags: PREG_UNMATCHED_AS_NULL,
        ) ?? throw new RuntimeException(preg_last_error_msg());
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
