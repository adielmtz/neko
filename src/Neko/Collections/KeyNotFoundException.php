<?php declare(strict_types=1);
namespace Neko\Collections;

use Exception;

/**
 * This exception is thrown when a key specified for accessing an element in a collection does not match any key in the
 * collection.
 */
class KeyNotFoundException extends Exception
{
}
