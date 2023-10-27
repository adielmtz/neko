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
    /**
     * KeyNotFoundException constructor.
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
