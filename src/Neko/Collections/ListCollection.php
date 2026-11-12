<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use OutOfBoundsException;

/**
 * Represents a collection of items that can be accessed by index.
 */
interface ListCollection extends ArrayAccess, SequencedCollection
{
    /**
     * Determines whether the list contains all specified items.
     *
     * @param iterable $items The iterable containing the items to search for.
     *
     * @return bool True if the list contains all items; otherwise, false.
     */
    public function containsAll(iterable $items): bool;

    /**
     * Appends all items from the specified iterable to the end of the list,
     * preserving the order in which they are yielded.
     *
     * @param iterable $items The iterable containing the items to append.
     *
     * @return void
     */
    public function addAll(iterable $items): void;

    /**
     * Returns the item at the specified index.
     *
     * @param int $index The zero-based index of the item to retrieve.
     *
     * @return mixed
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function get(int $index): mixed;

    /**
     * Replaces the item at the specified index with the given item.
     *
     * @param int $index The zero-based index of the item to replace.
     * @param mixed $item The item to store at the specified index.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function set(int $index, mixed $item): void;

    /**
     * Inserts an item at the specified index.
     * If the index equals the number of items in the list, the item is appended to the end.
     *
     * @param int $index The zero-based index at which to insert the item.
     * @param mixed $item The item to insert.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function insert(int $index, mixed $item): void;

    /**
     * Inserts all items from the specified iterable at the specified index,
     * preserving the order in which they are yielded.
     *
     * @param int $index The zero-based index at which to insert the items.
     * @param iterable $items The iterable containing the items to insert.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function insertAll(int $index, iterable $items): void;

    /**
     * Removes the item at the specified index.
     *
     * @param int $index The zero-based index of the item to remove.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function removeAt(int $index): void;

    /**
     * Returns the index of the first occurrence of the specified item, or -1 if the item is not found.
     *
     * @param mixed $item The item to search for.
     *
     * @return int
     */
    public function indexOf(mixed $item): int;

    /**
     * Returns the index of the last occurrence of the specified item, or -1 if the item is not found.
     *
     * @param mixed $item The item to search for.
     *
     * @return int
     */
    public function lastIndexOf(mixed $item): int;
}
