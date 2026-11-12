<?php declare(strict_types=1);
namespace Neko\Collections;

use OutOfBoundsException;

/**
 * This exception is thrown when a specified key does not match any key in the map.
 */
final class KeyNotFoundException extends OutOfBoundsException
{
}
