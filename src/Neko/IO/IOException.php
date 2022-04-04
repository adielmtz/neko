<?php declare(strict_types=1);
namespace Neko\IO;

use Exception;
use Throwable;
use function error_clear_last;
use function error_get_last;

/**
 * This exception is thrown then an IO error occurs.
 */
class IOException extends Exception
{
    /**
     * Throws an IOException using the last error message.
     *
     * @param string $message The default message for the exception if there is no error to report.
     *
     * @return void
     * @throws IOException
     */
    public static function throwFromLastError(string $message = ''): void
    {
        $error = error_get_last();
        $code = 0;
        if ($error !== null) {
            error_clear_last();
            $message = $error['message'];
            $code = $error['type'];
        }

        throw new IOException($message, $code);
    }

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
