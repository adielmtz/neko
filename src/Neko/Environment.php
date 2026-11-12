<?php declare(strict_types=1);
namespace Neko;

use RuntimeException;
use function array_filter;
use function function_exists;
use function getcwd;
use function getenv;
use function gethostname;
use function getmypid;
use function posix_geteuid;
use function posix_getpwuid;
use function preg_last_error_msg;
use function preg_replace_callback;
use function sys_get_temp_dir;
use const PHP_INT_SIZE;
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
    public const bool IS_WINDOWS_MACHINE = PHP_OS_FAMILY === 'Windows';

    /**
     * Indicates whether the current platform is Unix-like, such as Linux, macOS, BSD or Solaris.
     */
    public const bool IS_UNIX_MACHINE = PHP_OS_FAMILY === 'Linux'
        || PHP_OS_FAMILY === 'Darwin'
        || PHP_OS_FAMILY === 'BSD'
        || PHP_OS_FAMILY === 'Solaris';

    /**
     * Indicates whether the current process is a 64-bit process.
     */
    public const bool IS_64BIT_PROCESS = PHP_INT_SIZE === 8;

    /**
     * Returns the value of the specified environment variable.
     * A variable that is defined with an empty value is treated as undefined.
     *
     * @param string $name The name of the environment variable.
     *
     * @return string|null The value of the environment variable, or null if it is not defined or is empty.
     */
    public static function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? null : $value;
    }

    /**
     * Returns all environment variables of the current process.
     * Variables that are defined with an empty value are omitted.
     *
     * @return array<string, string> An array that maps the name of each environment variable to its value.
     */
    public static function getEnvironmentVariables(): array
    {
        return array_filter(getenv(), static fn(string $value): bool => $value !== '');
    }

    /**
     * Returns the path of the directory used for temporary files.
     *
     * The path is determined by the sys_temp_dir setting if it is set; otherwise, by TMPDIR on Unix-like platforms,
     * or by TMP, TEMP and USERPROFILE on Windows. The directory is not guaranteed to exist or to be writable.
     *
     * @return string The path of the temporary directory, without a trailing directory separator.
     */
    public static function getTempDirectory(): string
    {
        return sys_get_temp_dir();
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
     * Returns the host name of the local machine.
     *
     * @return string|null The host name of the local machine, or null if it cannot be determined.
     */
    public static function getMachineName(): ?string
    {
        $name = gethostname();
        return $name === false || $name === '' ? null : $name;
    }

    /**
     * Returns the name of the user who is running the current process.
     *
     * On Windows, USERNAME is used. On other platforms, the name of the effective user is taken from the user
     * database if the POSIX extension is available; otherwise, USER is checked, followed by LOGNAME.
     *
     * @return string|null The name of the user, or null if it cannot be determined.
     */
    public static function getUserName(): ?string
    {
        if (self::IS_WINDOWS_MACHINE) {
            return self::getEnvironmentVariable('USERNAME');
        }

        if (function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_geteuid());
            if ($user !== false && $user['name'] !== '') {
                return $user['name'];
            }
        }

        return self::getEnvironmentVariable('USER') ?? self::getEnvironmentVariable('LOGNAME');
    }

    /**
     * Returns the identifier of the current process.
     *
     * @return int|null The identifier of the current process, or null if it cannot be determined.
     */
    public static function getProcessId(): ?int
    {
        $pid = getmypid();
        return $pid === false ? null : $pid;
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
        if (self::IS_WINDOWS_MACHINE) {
            $profile = self::getEnvironmentVariable('USERPROFILE');
            if ($profile !== null) {
                return $profile;
            }

            $drive = self::getEnvironmentVariable('HOMEDRIVE');
            $path = self::getEnvironmentVariable('HOMEPATH');
            if ($drive !== null && $path !== null) {
                return $drive . $path;
            }

            return self::getEnvironmentVariable('HOME');
        }

        $home = self::getEnvironmentVariable('HOME');
        if ($home !== null) {
            return $home;
        }

        if (function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_geteuid());
            if ($user !== false && $user['dir'] !== '') {
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
    public static function expandEnvironmentVariables(string $str): string
    {
        return preg_replace_callback(
            '/\$(?:\{([A-Za-z_]\w*)\}|([A-Za-z_]\w*))/',
            static fn(array $matches): string => self::getEnvironmentVariable($matches[1] ?? $matches[2]) ?? $matches[0],
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
