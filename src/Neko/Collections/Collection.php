<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;

/**
 * Defines methods for collection manipulation.
 */
interface Collection extends Countable, IteratorAggregate
{
    /**
     * Returns true if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Removes all values from the collection.
     */
    public function clear(): void;

    /**
     * Returns true if the collection contains the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function contains(mixed $value): bool;

    /**
     * Copies the values of the collection to an array.
     *
     * @param array $destination The destination array.
     * @param int $index The index in $destination at which copy begins.
     */
    public function copyTo(array &$destination, int $index = 0): void;

    /**
     * Returns a one-dimension array containing all the values in the collection.
     *
     * @return array
     */
    public function toArray(): array;
}
