<?php declare(strict_types=1);
namespace Neko;

use Exception;
use Throwable;

/**
 * Thrown when a method call is not valid in its current state.
 */
class InvalidOperationException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
