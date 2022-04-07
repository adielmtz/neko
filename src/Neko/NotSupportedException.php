<?php declare(strict_types=1);
namespace Neko;

use LogicException;

/**
 * This exception is thrown when a method or function call is not supported or its functionality is not available,
 * such as trying to call a function that requires a specific extension to be enabled.
 */
class NotSupportedException extends LogicException
{
}
