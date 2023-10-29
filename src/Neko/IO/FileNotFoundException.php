<?php declare(strict_types=1);
namespace Neko\IO;

use Throwable;

/**
 * This exception is thrown when attempting to access a file that is not found or does not exist.
 */
final class FileNotFoundException extends IOException
{
    /**
     * FileNotFoundException constructor.
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
