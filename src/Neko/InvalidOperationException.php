<?php declare(strict_types=1);
namespace Neko;

use Exception;
use Throwable;

/**
 * This exception is thrown when a method or function call is not valid in its current state.
 */
class InvalidOperationException extends Exception
{
    /**
     * InvalidOperationException constructor.
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
