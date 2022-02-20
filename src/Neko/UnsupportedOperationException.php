<?php declare(strict_types=1);
namespace Neko;

use LogicException;
use Throwable;

/**
 * Thrown when an invoked method is not supported or its functionality is not available.
 * Represents an error in the program logic. This kind of exception should lead directly to a fix in your code.
 */
class UnsupportedOperationException extends LogicException
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
