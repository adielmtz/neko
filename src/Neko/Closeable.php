<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines the common interface for objects that hold a resource that must be released explicitly.
 */
interface Closeable
{
    /**
     * Closes the object and releases any resources associated with it.
     * Calling this method on an object that is already closed has no effect.
     * Implementation of this method SHOULD NOT throw exceptions.
     *
     * @return void
     */
    public function close(): void;
}
