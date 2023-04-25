<?php declare(strict_types=1);
namespace Neko\Collections;

use OutOfBoundsException;

/**
 * Defines an ordered collection of elements that can be accessed by index.
 */
interface ListCollection extends Collection
{
    /**
     * Adds an element to the end of the list.
     *
     * @param mixed $value The element to add to the list.
     *
     * @return void
     */
    public function add(mixed $value): void;

    /**
     * Inserts a collection of elements to the end of the list.
     *
     * @param iterable $items The collection of elements to insert to the list.
     *
     * @return void
     */
    public function addAll(iterable $items): void;

    /**
     * Returns the element at the specified index.
     *
     * @param int $index The zero-based index of the element to return.
     *
     * @return mixed
     * @throws OutOfBoundsException if the index is out of range.
     */
    public function get(int $index): mixed;

    /**
     * Replaces the element at the specified index with a different element.
     *
     * @param int $index The zero-based index of the element to replace.
     * @param mixed $value The new element.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range.
     */
    public function set(int $index, mixed $value): void;

    /**
     * Inserts an element at the specified index.
     *
     * @param int $index The zero-based index at which the element should be inserted.
     * If the index is equal to the size of the list, the element is added to the end of the list.
     * @param mixed $value The element to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range.
     */
    public function insert(int $index, mixed $value): void;

    /**
     * Inserts a collection of elements at the specified index in the list.
     *
     * @param int $index The zero-based index at which the collection should be inserted.
     * If the index is equal to the size of the list, the collection is added to the end of the list.
     * @param iterable $items The collection of elements to insert to the list.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of range.
     */
    public function insertAll(int $index, iterable $items): void;

    /**
     * Removes the first occurrence of an element in the list.
     *
     * @param mixed $value The element to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
     */
    public function remove(mixed $value): bool;

    /**
     * Removes the element at the specified index.
     *
     * @param int $index The zero-based index of the element to remove.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range.
     */
    public function removeAt(int $index): void;

    /**
     * Removes a range of elements from the list.
     *
     * @param int $index The zero-based inclusive index where the range starts.
     * @param int|null $count The number of elements to remove. If $count is less than or equal to zero, nothing will
     * be removed. If $count is null or greater than ArrayList::count(), all elements from $index to the end of the
     * list will be removed.
     *
     * @return int The number of elements removed from the list.
     * @throws OutOfBoundsException if the index is out of range.
     */
    public function removeRange(int $index, ?int $count = null): int;

    /**
     * Returns the zero-based index of the first occurrence of the element in the list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the first occurrence of the element or -1 if the list does not
     * contain the element.
     */
    public function indexOf(mixed $value): int;

    /**
     * Returns the zero-based index of the last occurrence of the element in the list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the last occurrence of the element or -1 if the list does not
     * contain the element.
     */
    public function lastIndexOf(mixed $value): int;
}
