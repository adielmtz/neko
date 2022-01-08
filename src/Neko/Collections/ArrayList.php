<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use Traversable;
use function assert;
use function count;
use function floor;
use function min;
use function sort;
use function usort;
use const SORT_REGULAR;

/**
 * Represents a list of values that can be accessed by index.
 */
class ArrayList implements ArrayAccess, IndexedList
{
    private array $items = [];
    private int $length = 0;
    private int $version = 0;

    /**
     * ArrayList constructor.
     *
     * @param iterable|null $items A collection of values that will be copied to the list.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            foreach ($items as $value) {
                $this->add($value);
            }
        }
    }

    /**
     * Returns true if the list is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all values from the list.
     */
    public function clear(): void
    {
        $this->items = [];
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the list contains the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return $this->indexOf($value) > -1;
    }

    /**
     * Copies the values of the list to an array.
     *
     * @param array $destination The destination array.
     * @param int $index The index in $destination at which copy begins.
     */
    public function copyTo(array &$destination, int $index = 0): void
    {
        for ($i = 0; $i < $this->length; $i++) {
            $destination[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns a one-dimension array containing all the values in the list.
     *
     * @return array
     */
    public function toArray(): array
    {
        $values = [];
        $this->copyTo($values);
        return $values;
    }

    /**
     * Gets an iterator instance for the list.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new IndexedListIterator($this->items, $this->length, $this->version);
    }

    /**
     * Returns the number of values in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Adds a value at the end of the list.
     *
     * @param mixed $value
     */
    public function add(mixed $value): void
    {
        $this->items[$this->length++] = $value;
        $this->version++;
    }

    /**
     * Adds a collection of values at the end of the list.
     *
     * @param array|Collection $items A collection of values that will be copied to the list.
     */
    public function addRange(array|Collection $items): void
    {
        $this->insertRange($this->length, $items);
    }

    /**
     * Gets the value at the specified position in the list.
     *
     * @param int $index The zero-based index of the value to return.
     *
     * @return mixed
     * @throws OutOfBoundsException If the index is less than zero or is equal or greater than the length of the list.
     */
    public function get(int $index): mixed
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        return $this->items[$index];
    }

    /**
     * Sets a value at the specified position in the list.
     *
     * @param int $index The zero-based index in the list.
     * @param mixed $value The value to set.
     *
     * @throws OutOfBoundsException If the index is less than zero or is equal or greater than the length of the list.
     */
    public function set(int $index, mixed $value): void
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        $this->items[$index] = $value;
        $this->version++;
    }

    /**
     * Inserts a value at the specified position in the list.
     *
     * @param int $index The zero-based index at which the value will be inserted.
     * The index can be the length of the list, in which case it will insert the value at the end.
     * @param mixed $value The value to insert.
     *
     * @throws OutOfBoundsException If the index is less than zero or greater than the length of the list.
     */
    public function insert(int $index, mixed $value): void
    {
        if ($index < 0 || $index > $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than or equal to the length of the list');
        }

        for ($i = $this->length; $i > $index; $i--) {
            $this->items[$i] = $this->items[$i - 1];
        }

        $this->items[$index] = $value;
        $this->length++;
        $this->version++;
    }

    /**
     * Inserts a collection of values at the specified position in the list.
     *
     * @param int $index The zero-based index at which the value will be inserted.
     * The index can be the length of the list, in which case it will insert the value at the end.
     * @param array|Collection $items A collection of values that will be inserted to the list.
     *
     * @throws OutOfBoundsException If the index is less than zero or greater than the length of the list.
     */
    public function insertRange(int $index, array|Collection $items): void
    {
        if ($index < 0 || $index > $this->length) {
            throw new OutOfBoundsException('Index must be within the bounds of the list');
        }

        if ($items instanceof Collection) {
            $items = $items->toArray();
        }

        $length = count($items);
        $newLength = $this->length + $length;

        // == Example ==
        // $items: [X,Y,Z], length: 3, insert at index: 3
        // We need to move the values from the index 3 onwards 3 positions
        // to the right, iterating in reverse order.
        //
        // $this->items:
        //  initial state -> [A,B,C,D,E,F,G,H, , , , , ]
        //  first loop    -> [A,B,C, , , ,D,E,F,G,H, , ]
        //  second loop   -> [A,B,C,X,Y,Z,D,E,F,G,H, , ]
        for ($i = $newLength - 1; $i >= $index + $length; $i--) {
            $this->items[$i] = $this->items[$i - $length];
        }

        for ($i = 0; $i < $length; $i++) {
            $this->items[$index++] = $items[$i];
        }

        $this->length = $newLength;
        $this->version++;
    }

    /**
     * Removes the first occurrence of the value in the list.
     *
     * @param mixed $value The value to remove.
     *
     * @return bool Returns true if the value was found and removed from the list.
     */
    public function remove(mixed $value): bool
    {
        $index = $this->indexOf($value);
        if ($index > -1) {
            $this->removeAt($index);
            return true;
        }

        return false;
    }

    /**
     * Removes the value at the specified position in the list.
     *
     * @param int $index The zero-based index of the value to be removed.
     *
     * @throws OutOfBoundsException If the index is less than zero or is equal or greater than the length of the list.
     */
    public function removeAt(int $index): void
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        $this->length--;
        for (; $index < $this->length; $index++) {
            $this->items[$index] = $this->items[$index + 1];
        }

        $this->items[$this->length] = null;
        $this->version++;
    }

    /**
     * Removes a range of values from the list.
     *
     * @param int $index The zero-based index where the range of values to be removed starts.
     * @param int|null $count The number of values to remove. If $count is less than or equal to zero,
     * nothing will be removed. If $count is NULL, the values are removed through the end of the list.
     *
     * @return int The number of values removed from the list.
     * @throws OutOfBoundsException If the index is less than zero or greater than the length of the list.
     */
    public function removeRange(int $index, ?int $count = null): int
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        if ($count === null) {
            $count = $this->length - $index;
        }

        $removed = 0;
        if ($count > 0) {
            while ($index < $this->length) {
                $this->items[$index] = $this->items[$index + $count] ?? null;
                $index++;
                $removed++;
            }

            $this->length -= min($count, $this->length);
            assert($this->length >= 0);
        }

        $this->version++;
        return $removed;
    }

    /**
     * Returns the zero-base index of the first occurrence of the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function indexOf(mixed $value): int
    {
        for ($i = 0; $i < $this->length; $i++) {
            if ($value === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the zero-base index of the last occurrence of the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function lastIndexOf(mixed $value): int
    {
        for ($i = $this->length - 1; $i >= 0; $i--) {
            if ($value === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the zero-based index of the value using a binary search algorithm.
     *
     * @param mixed $value The value to search.
     * @param callable|null $comparator A comparator function that must take 2 parameters and return an integer.
     * If a comparator is not provided, a default one will be used: $a <=> $b
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function binarySearch(mixed $value, ?callable $comparator = null): int
    {
        if (!$this->isEmpty()) {
            if ($comparator === null) {
                // Default comparator
                $comparator = fn($a, $b) => $a <=> $b;
            }

            $l = 0;
            $h = $this->length - 1;
            while ($l <= $h) {
                $m = (int) floor(($l + $h) / 2);
                $c = $comparator($value, $this->items[$m]);

                if ($c === 0) {
                    return $m;
                } else if ($c < 0) {
                    $h = $m - 1;
                } else {
                    $l = $m + 1;
                }
            }
        }

        return -1;
    }

    /**
     * Finds the zero-base index of the first value that matches the condition.
     *
     * @param callable $match A function that must take 1 parameter and return a boolean value.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function findIndex(callable $match): int
    {
        for ($i = 0; $i < $this->length; $i++) {
            if ($match($this->items[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Finds the zero-base index of the last value that matches the condition.
     *
     * @param callable $match A function that must take 1 parameter and return a boolean value.
     *
     * @return int The index in the list or -1 if the value was not found.
     */
    public function findLastIndex(callable $match): int
    {
        for ($i = $this->length - 1; $i >= 0; $i--) {
            if ($match($this->items[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Reverses the order of the values in the list.
     */
    public function reverse(): void
    {
        $start = 0;
        $end = $this->length - 1;
        while ($start < $end) {
            $a = $this->items[$start];
            $b = $this->items[$end];

            $this->items[$start] = $b;
            $this->items[$end] = $a;

            $start++;
            $end--;
        }

        $this->version++;
    }

    /**
     * Sorts the list.
     *
     * @param callable|null $comparator A comparison function that must return an integer less than, equal to,
     * or greater than zero if the first argument is considered to be respectively less than, equal to,
     * or greater than the second.
     */
    public function sort(?callable $comparator = null): void
    {
        $items = $this->items;
        if (count($items) > $this->length) {
            $items = $this->toArray();
        }

        if ($comparator === null) {
            sort($items, SORT_REGULAR);
        } else {
            usort($items, $comparator);
        }

        $this->items = $items;
        $this->version++;
    }

    /**
     * Returns a shallow copy of a portion of the list into a new list.
     *
     * @param int $index The zero-based index where the range of values to copy begins
     * @param int|null $count The number of values to copy. If $count is less than or equal to zero,
     * nothing will be copied and an empty list will be returned.
     * If $count is NULL, the values are copied through the end of the list.
     *
     * @return ArrayList
     * @throws OutOfBoundsException If the index is less than zero or greater than the length of the list.
     */
    public function slice(int $index, ?int $count = null): ArrayList
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        if ($count === null || $count > $this->length) {
            $count = $this->length;
        }

        $slice = new ArrayList();
        for ($i = 0; $i < $count; $i++) {
            $slice->add($this->items[$index++]);
        }

        return $slice;
    }

    /**
     * Returns a new list with all the values in the list that pass the condition.
     *
     * @param callable $match A filter function that must take 1 parameter and return a boolean value.
     * If the function returns true, the value will be copied into the new list; otherwise is ignored.
     *
     * @return ArrayList
     */
    public function filter(callable $match): ArrayList
    {
        $filter = new ArrayList();
        for ($i = 0; $i < $this->length; $i++) {
            $value = $this->items[$i];
            if ($match($value)) {
                $filter->add($value);
            }
        }

        return $filter;
    }

    /**
     * Returns a new list containing all values after applying the callback function to each one.
     *
     * @param callable $callback A function that must take 1 parameter and return a value that will be
     * copied to the new list.
     *
     * @return ArrayList
     */
    public function map(callable $callback): ArrayList
    {
        $map = new ArrayList();
        for ($i = 0; $i < $this->length; $i++) {
            $value = $callback($this->items[$i]);
            $map->add($value);
        }

        return $map;
    }

    /**
     * Returns true if all the values in the list pass the condition.
     *
     * @param callable $match A function that must take 1 parameter and return a boolean value.
     *
     * @return bool
     */
    public function trueForAll(callable $match): bool
    {
        for ($i = 0; $i < $this->length; $i++) {
            if (!$match($this->items[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if at least one of the values in the list pass the condition.
     *
     * @param callable $match A function that must take 1 parameter and return a boolean value.
     *
     * @return bool
     */
    public function trueForAny(callable $match): bool
    {
        for ($i = 0; $i < $this->length; $i++) {
            if ($match($this->items[$i])) {
                return true;
            }
        }

        return false;
    }

    #region ArrayAccess methods
    public function offsetExists(mixed $offset): bool
    {
        return $offset >= 0 && $offset < $this->length;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->removeAt($offset);
    }
    #endregion
}
