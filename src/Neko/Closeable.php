<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines an object that can be closed, such as a FileStream.
 */
interface Closeable
{
    public function close(): void;
}
