<?php declare(strict_types=1);
namespace Neko\Collections;

use OutOfBoundsException;

/**
 * Defines an ordered collection of elements that can be accessed by index.
 */
interface ListCollection extends Collection
{
    /**
     * Adds the element to the end of the list.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    public function add(mixed $item): void;

    /**
     * Adds all the elements of the collection to the end of the list.
     *
     * @param iterable $items The collection to add.
     *
     * @return void
     */
    public function addAll(iterable $items): void;

    /**
     * Gets the element at the specified index.
     *
     * @param int $index The index of the element to return.
     *
     * @return mixed
     * @throws OutOfBoundsException if the index is not valid.
     */
    public function get(int $index): mixed;

    /**
     * Sets the element at the specified index.
     *
     * @param int $index The index of the element to set.
     * @param mixed $item The element to set.
     *
     * @return void
     * @throws OutOfBoundsException if the index is not valid.
     */
    public function set(int $index, mixed $item): void;

    /**
     * Inserts an element at the specified index.
     *
     * @param int $index The index at which the element should be inserted.
     * @param mixed $item The element to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is not valid.
     */
    public function insert(int $index, mixed $item): void;

    /**
     * Inserts all the elements of the collection at the specified index.
     *
     * @param int $index The index at which the collection should be inserted.
     * @param iterable $items The collection to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is not valid.
     */
    public function insertAll(int $index, iterable $items): void;

    /**
     * Removes the first occurrence of the element from the list.
     *
     * @param mixed $item The element to remove.
     *
     * @return bool True if the element was successfully removed; otherwise, false.
     */
    public function remove(mixed $item): bool;

    /**
     * Removes the element at the specified index of the list.
     *
     * @param int $index The index of the element to remove.
     *
     * @return void
     * @throws OutOfBoundsException if the index is not valid.
     */
    public function removeAt(int $index): void;

    /**
     * Returns the index of the first occurrence of the element.
     *
     * @param mixed $item The element to search.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    public function indexOf(mixed $item): int;

    /**
     * Returns the index of the last occurrence of the element.
     *
     * @param mixed $item The element to search.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    public function lastIndexOf(mixed $item): int;
}
