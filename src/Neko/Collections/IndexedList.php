<?php declare(strict_types=1);
namespace Neko\Collections;

use OutOfBoundsException;

/**
 * Defines a collection of values that can be individually accessed by an index.
 */
interface IndexedList extends Collection
{
    /**
     * Adds a value at the end of the list.
     *
     * @param mixed $value
     */
    public function add(mixed $value): void;

    /**
     * Gets the value at the specified position in the list.
     *
     * @param int $index The zero-based index of the value to return.
     *
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function get(int $index): mixed;

    /**
     * Sets a value at the specified position in the list.
     *
     * @param int $index The zero-based index in the list.
     * @param mixed $value The value to set.
     *
     * @throws OutOfBoundsException
     */
    public function set(int $index, mixed $value): void;

    /**
     * Inserts a value at the specified position in the list.
     *
     * @param int $index The zero-based index at which the value will be inserted.
     * @param mixed $value The value to insert.
     *
     * @throws OutOfBoundsException
     */
    public function insert(int $index, mixed $value): void;

    /**
     * Removes the first occurrence of the value in the list.
     *
     * @param mixed $value The value to remove.
     *
     * @return bool Returns true if the value was found and removed from the list.
     */
    public function remove(mixed $value): bool;

    /**
     * Removes the value at the specified position in the list.
     *
     * @param int $index The zero-based index of the value to be removed.
     *
     * @throws OutOfBoundsException
     */
    public function removeAt(int $index): void;

    /**
     * Returns the zero-base index of the first occurrence of the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function indexOf(mixed $value): int;

    /**
     * Returns the zero-base index of the last occurrence of the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function lastIndexOf(mixed $value): int;
}
