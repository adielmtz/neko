<?php declare(strict_types=1);
namespace Neko;

use LogicException;
use Throwable;

/**
 * This exception is thrown when a method or function call is not supported or its functionality is not available,
 * such as trying to call a function that requires a specific extension to be enabled.
 */
class NotSupportedException extends LogicException
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
