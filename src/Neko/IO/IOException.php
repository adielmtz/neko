<?php declare(strict_types=1);
namespace Neko\IO;

use Exception;
use Throwable;
use function error_clear_last;
use function error_get_last;

/**
 * This exception is thrown when an IO error occurs.
 */
class IOException extends Exception
{
    /**
     * Returns an IOException that can be thrown, using the last error message.
     *
     * @param string $defaultMessage The default message for the exception if there is no reported error.
     *
     * @return IOException
     */
    public static function fromLastErrorOrDefault(string $defaultMessage = ''): IOException
    {
        $error = error_get_last();
        $code = 0;
        if ($error !== null) {
            error_clear_last();
            $defaultMessage = $error['message'];
            $code = $error['type'];
        }

        return new IOException($defaultMessage, $code);
    }

    /**
     * IOException constructor.
     *
     * @param string $message The exception message to throw.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
