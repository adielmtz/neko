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
     * Throws an IOException if error_get_last() reports an error.
     *
     * @throws IOException
     */
    public static function throwFromLastError(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            error_clear_last();
            throw new IOException($error['message']);
        }
    }

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
