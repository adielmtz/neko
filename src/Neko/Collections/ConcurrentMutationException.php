<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;

/**
 * This exception is thrown when a collection is concurrently modified during iteration.
 */
final class ConcurrentMutationException extends InvalidOperationException
{
}
