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
use function sprintf;
use function usort;
use const SORT_REGULAR;

/**
 * Represents an ordered list of elements that can be accessed by index.
 */
class ArrayList implements ArrayAccess, ListCollection
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
     * Returns true if the list contains no elements.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all elements from the list.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the list contains a specific element.
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
     * Copies the elements of the list to an array.
     *
     * @param array $array
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    public function copyTo(array &$array, int $index = 0): void
    {
        for ($i = 0; $i < $this->length; $i++) {
            $array[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns an array containing all the elements of the list.
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
     * Returns an iterator over the elements in the list.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ListCollectionIterator($this->items, $this->length, $this->version);
    }

    /**
     * Returns the number of elements in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Adds an element to the end of the list.
     *
     * @param mixed $value The element to add to the list.
     *
     * @return void
     */
    public function add(mixed $value): void
    {
        $this->items[$this->length++] = $value;
        $this->version++;
    }

    /**
     * Inserts a collection of elements to the end of the list.
     *
     * @param array|Collection $items The collection of elements to insert to the list.
     *
     * @return void
     */
    public function addRange(array|Collection $items): void
    {
        $this->insertRange($this->length, $items);
    }

    /**
     * Returns the element at the specified index.
     *
     * @param int $index The zero-based index of the element to return.
     *
     * @return mixed
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function get(int $index): mixed
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index)
            );
        }

        return $this->items[$index];
    }

    /**
     * Replaces the element at the specified index with a different element.
     *
     * @param int $index The zero-based index of the element to replace.
     * @param mixed $value The new element.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function set(int $index, mixed $value): void
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index)
            );
        }

        $this->items[$index] = $value;
        $this->version++;
    }

    /**
     * Inserts an element at the specified index.
     *
     * @param int $index The zero-based index at which the element should be inserted.
     * If the index is equal to the size of the list, the element is added to the end of the list.
     * @param mixed $value The element to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    public function insert(int $index, mixed $value): void
    {
        if ($index < 0 || $index > $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index > ArrayList::count())', $index)
            );
        }

        for ($i = $this->length; $i > $index; $i--) {
            $this->items[$i] = $this->items[$i - 1];
        }

        $this->items[$index] = $value;
        $this->length++;
        $this->version++;
    }

    /**
     * Inserts a collection of elements at the specified index in the list.
     *
     * @param int $index The zero-based index at which the collection should be inserted.
     * If the index is equal to the size of the list, the collection is added to the end of the list.
     * @param array|Collection $items The collection of elements to insert to the list.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    public function insertRange(int $index, array|Collection $items): void
    {
        if ($index < 0 || $index > $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index > ArrayList::count())', $index)
            );
        }

        if ($items instanceof Collection) {
            $items = $items->toArray();
        }

        $length = count($items);
        $newLength = $this->length + $length;

        // == Example ==
        // insert [X,Y,Z] at index 3
        // we have to move the elements from index 3, 3 positions to the right
        // iterating in reverse order.
        //
        // $this->items:
        // initial state -> [A,B,C,D,E,F,G,H, , , , , ]
        // first loop    -> [A,B,C, , , ,D,E,F,G,H, , ]
        // second loop   -> [A,B,C,X,Y,Z,D,E,F,G,H, , ]
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
     * Removes the first occurrence of an element in the list.
     *
     * @param mixed $value The element to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
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
     * Removes the element at the specified index.
     *
     * @param int $index The zero-based index of the element to remove.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function removeAt(int $index): void
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index)
            );
        }

        $this->length--;
        for (; $index < $this->length; $index++) {
            $this->items[$index] = $this->items[$index + 1];
        }

        $this->items[$this->length] = null;
        $this->version++;
    }

    /**
     * Removes a range of elements from the list.
     *
     * @param int $index The zero-based inclusive index where the range starts.
     * @param int|null $count The number of elements to remove. If $count is less than or equal to zero, nothing will
     *     be removed. If $count is null, all elements from $index to the end of the list will be removed.
     *
     * @return int The number of elements removed from the list.
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function removeRange(int $index, ?int $count = null): int
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index)
            );
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
     * Removes all elements of the list that satisfy the given predicate.
     *
     * @param callable $match A predicate function which returns true for elements to remove.
     *
     * @return int The number of elements removed.
     */
    public function removeIf(callable $match): int
    {
        $freeIndex = 0;
        while ($freeIndex < $this->length && !$match($this->items[$freeIndex])) {
            $freeIndex++;
        }

        if ($freeIndex >= $this->length) {
            return 0;
        }

        $current = $freeIndex + 1;
        while ($current < $this->length) {
            while ($current < $this->length && $match($this->items[$current])) {
                $current++;
            }

            if ($current < $this->length) {
                $this->items[$freeIndex++] = $this->items[$current++];
            }
        }

        $result = $this->length - $freeIndex;
        $this->length = $freeIndex;

        // Clean up unused indexes
        $size = count($this->items);
        for ($i = $this->length; $i < $size; $i++) {
            $this->items[$i] = null;
        }

        return $result;
    }

    /**
     * Returns the zero-based index of the first occurrence of the element in the list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the first occurrence of the element or -1 if the list does not
     * contain the element.
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
     * Returns the zero-based index of the last occurrence of the element in the list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the last occurrence of the element or -1 if the list does not
     * contain the element.
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
     * Returns the zero-based index of the element using a binary search algorithm.
     * This method assumes that the list is sorted.
     *
     * @param mixed $value The element to search.
     * @param callable|null $comparator A comparator function to use when comparing the elements or NULL to use a
     *     default comparator.
     *
     * @return int The zero-based index of the element or -1 if the list does not contain the element.
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
     * Returns the zero-based index of the first occurrence of the element that matches the predicate.
     *
     * @param callable $match A predicate function which returns true for element's index to return.
     *
     * @return int The zero-based index of the first occurrence of the element or -1 if the list does not
     * contain the element.
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
     * Returns the zero-based index of the last occurrence of the element that matches the predicate.
     *
     * @param callable $match A predicate function which returns true for element's index to return.
     *
     * @return int The zero-based index of the last occurrence of the element or -1 if the list does not
     * contain the element.
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
     * Reverses the order of the elements in the list.
     *
     * @return void
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
     * Sorts the elements in the list.
     *
     * @param callable|null $comparator A comparator function to use when comparing the elements or NULL to use a
     *     default comparator.
     *
     * @return void
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
     * Returns a range (or a portion) of the list.
     *
     * @param int $index The zero-based inclusive index where the range starts.
     * @param int|null $count The number of elements in the range. If $count is less than or equal to zero, nothing will
     *     be copied. If $count is null, all elements from $index to the end of the list will be copied.
     *
     * @return ArrayList A shallow copy of a range of elements in the list.
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function slice(int $index, ?int $count = null): ArrayList
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index)
            );
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
     * Returns a new list containing all the elements of the list that satisfy the given predicate.
     *
     * @param callable $match A predicate function which returns true for elements to keep.
     *
     * @return ArrayList A shallow copy of the elements that satisfy the predicate.
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
     * Returns a new list containing all the elements of the list after applying a callback function.
     *
     * @param callable $callback The callback function which returns the transformed element.
     *
     * @return ArrayList A new list that contains the transformed elements.
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
     * Executes the given function for each element of the list.
     *
     * @param callable $action The function to execute for each element.
     *
     * @return void
     * @throws InvalidOperationException if an element in the list has been modified.
     */
    public function forEach(callable $action): void
    {
        $version = $this->version;
        for ($i = 0; $i < $this->length; $i++) {
            if ($version !== $this->version) {
                throw new InvalidOperationException('List was modified');
            }

            $action($this->items[$i]);
        }
    }

    /**
     * Returns true if all elements in the list satisfy the given predicate.
     *
     * @param callable $match A predicate function which returns true for elements that satisfy the condition.
     *
     * @return bool
     */
    public function all(callable $match): bool
    {
        for ($i = 0; $i < $this->length; $i++) {
            if (!$match($this->items[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if at least one element in the list satisfies the given predicate.
     *
     * @param callable $match A predicate function which returns true for elements that satisfy the condition.
     *
     * @return bool
     */
    public function any(callable $match): bool
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
