<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;

/**
 * Defines the common interface for sequenced collection data structures.
 */
interface SequencedCollection extends Collection
{
    /**
     * Adds an item to the beginning of the collection.
     *
     * @param mixed $item The item to add.
     *
     * @return void
     */
    public function addFirst(mixed $item): void;

    /**
     * Adds an item to the end of the collection.
     *
     * @param mixed $item The item to add.
     *
     * @return void
     */
    public function addLast(mixed $item): void;

    /**
     * Returns the first item in the collection.
     *
     * @return mixed
     * @throws InvalidOperationException If the collection is empty.
     */
    public function getFirst(): mixed;

    /**
     * Returns the last item in the collection.
     *
     * @return mixed
     * @throws InvalidOperationException If the collection is empty.
     */
    public function getLast(): mixed;

    /**
     * Removes and returns the first item in the collection.
     *
     * @return mixed The removed item.
     * @throws InvalidOperationException If the collection is empty.
     */
    public function removeFirst(): mixed;

    /**
     * Removes and returns the last item in the collection.
     *
     * @return mixed The removed item.
     * @throws InvalidOperationException If the collection is empty.
     */
    public function removeLast(): mixed;

    /**
     * Reverses the order of items in the collection.
     *
     * @return void
     */
    public function reverse(): void;

    /**
     * Returns a new sequenced collection containing the items in reverse order.
     *
     * @return SequencedCollection A new collection with the items in reverse order.
     */
    public function toReversed(): SequencedCollection;
}
