<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;

/**
 * Defines methods to manipulate collections.
 */
interface Collection extends Countable, IteratorAggregate
{
    /**
     * Returns true if the collection contains no elements.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Removes all elements from the collection.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Returns true if the collection contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
    public function contains(mixed $item): bool;

    /**
     * Returns true if the collection contains all the elements in the specified collection.
     *
     * @param iterable $items The collection to search.
     *
     * @return bool
     */
    public function containsAll(iterable $items): bool;

    /**
     * Copies the elements of the collection to an array, starting at the specified index.
     *
     * @param array $array REF: The array where the elements of the collection will be copied.
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    public function copyTo(array &$array, int $index = 0): void;

    /**
     * Returns an array containing all the elements of the collection.
     *
     * @return array
     */
    public function toArray(): array;
}
