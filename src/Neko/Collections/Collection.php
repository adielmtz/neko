<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;

/**
 * The root interface for the collection hierarchy.
 * Defines methods to manipulate a collection of values.
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
     * Returns true if the collection contains a specific element.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function contains(mixed $value): bool;

    /**
     * Copies the elements of the collection to an array.
     *
     * @param array $array
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
