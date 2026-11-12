<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;

/**
 * Defines the common interface for collection data structures.
 */
interface Collection extends Countable, IteratorAggregate
{
    /**
     * Determines whether the collection is empty.
     *
     * @return bool True if the collection is empty; otherwise, false.
     */
    public function isEmpty(): bool;

    /**
     * Determines whether the collection contains a specific item.
     *
     * @param mixed $item The item to search for.
     *
     * @return bool True if the collection contains the item; otherwise, false.
     */
    public function contains(mixed $item): bool;

    /**
     * Adds an item to the collection.
     *
     * @param mixed $item The item to add.
     *
     * @return void
     */
    public function add(mixed $item): void;

    /**
     * Removes one occurrence of the specified item from the collection, if present.
     *
     * @param mixed $item The item to remove.
     *
     * @return bool True if an item was removed; otherwise, false.
     */
    public function remove(mixed $item): bool;

    /**
     * Removes all items from the collection.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Returns an array containing all items in the collection.
     *
     * @return array
     */
    public function toArray(): array;
}
