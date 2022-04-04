<?php declare(strict_types=1);
namespace Neko\Collections;

use Exception;
use Throwable;

/**
 * This exception is thrown when a key specified for accessing an element in a collection does not match any key in the
 * collection.
 */
class KeyNotFoundException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
