<?php declare(strict_types=1);
namespace Neko\Collections;

use Exception;

/**
 * This exception is thrown when a collection is concurrently modified during iteration.
 */
final class ConcurrentMutationException extends Exception
{
}
