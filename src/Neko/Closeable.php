<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines an object that can be closed, such as a FileStream.
 */
interface Closeable
{
    /**
     * Closes the resource or operation.
     *
     * @return void
     */
    public function close(): void;
}
