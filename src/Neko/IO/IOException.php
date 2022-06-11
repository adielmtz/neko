<?php declare(strict_types=1);
namespace Neko\IO;

use Exception;
use function error_clear_last;
use function error_get_last;

/**
 * This exception is thrown when an IO error occurs.
 */
class IOException extends Exception
{
    /**
     * Throws an IOException using the last error message.
     *
     * @param string $defaultMessage The default message for the exception if there is no error to report.
     *
     * @return void
     * @throws IOException
     */
    public static function throwLastErrorOrDefault(string $defaultMessage = ''): void
    {
        $error = error_get_last();
        $code = 0;
        if ($error !== null) {
            error_clear_last();
            $defaultMessage = $error['message'];
            $code = $error['type'];
        }

        throw new IOException($defaultMessage, $code);
    }
}
