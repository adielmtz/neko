<?php declare(strict_types=1);
namespace Neko\IO;

use Exception;
use Throwable;
use function error_clear_last;
use function error_get_last;

/**
 * Thrown when an IO error occurs.
 */
class IOException extends Exception
{
    /**
     * Throws an IOException using the last error message.
     *
     * @throws IOException
     */
    public static function throwFromLastError(): IOException
    {
        $error = error_get_last();
        $message = '';
        if ($error !== null) {
            error_clear_last();
            $message = $error['message'];
        }

        throw new IOException($message);
    }

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
