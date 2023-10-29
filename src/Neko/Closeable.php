<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines a method for closing any resource or task associated with an object.
 */
interface Closeable
{
    /**
     * Closes the resource or task and releases the associated resources.
     *
     * @return void
     */
    public function close(): void;
}
