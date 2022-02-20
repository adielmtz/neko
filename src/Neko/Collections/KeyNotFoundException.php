<?php declare(strict_types=1);
namespace Neko\Collections;

use Exception;
use Throwable;

class KeyNotFoundException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
