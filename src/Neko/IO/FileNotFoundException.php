<?php declare(strict_types=1);
namespace Neko\IO;

use Throwable;

/**
 * This exception is thrown when a file is not found or does not exists.
 */
final class FileNotFoundException extends IOException
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
