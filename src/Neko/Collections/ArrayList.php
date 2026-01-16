<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use Iterator;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use Override;
use Traversable;
use function assert;
use function count;
use function floor;
use function is_int;
use function iterator_to_array;
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
    private int $size = 0;
    private int $version = 0;

    /**
     * ArrayList constructor.
     *
     * @param iterable|null $items A collection of initial elements that will be copied to the list.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, false);
            $this->size = count($this->items);
        }
    }

    /**
     * Serializes the list.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserializes the list.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->items = $data;
        $this->size = count($data);
    }

    /**
     * Returns true if the list contains no elements.
     *
     * @return bool
     */
    #[Override]
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all elements from the list.
     *
     * @return void
     */
    #[Override]
    public function clear(): void
    {
        $this->items = [];
        $this->size = 0;
        $this->version++;
    }

    /**
     * Returns true if the list contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
    #[Override]
    public function contains(mixed $item): bool
    {
        return $this->indexOf($item) > -1;
    }

    /**
     * Returns true if the list contains all the elements in the specified collection.
     *
     * @param iterable $items The collection to search.
     *
     * @return bool
     */
    #[Override]
    public function containsAll(iterable $items): bool
    {
        foreach ($items as $value) {
            if (!$this->contains($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copies the elements of the list to an array, starting at the specified index.
     *
     * @param array $array REF: the array where the elements of the list will be copied.
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    #[Override]
    public function copyTo(array &$array, int $index = 0): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $array[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns an array containing all the elements of the list.
     *
     * @return array
     */
    #[Override]
    public function toArray(): array
    {
        $values = [];
        $this->copyTo($values);
        return $values;
    }

    /**
     * Gets an iterator that can traverse through the elements of the list.
     *
     * @return Iterator
     * @throws InvalidOperationException if the list was modified within the iterator.
     */
    #[Override]
    public function getIterator(): Iterator
    {
        $version = $this->version;
        for ($i = 0; $i < $this->size; $i++) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new InvalidOperationException('List was modified');
            }
        }
    }

    /**
     * Returns the number of elements in the list.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Adds the element to the end of the list.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    #[Override]
    public function add(mixed $item): void
    {
        $this->items[$this->size] = $item;
        $this->size++;
        $this->version++;
    }

    /**
     * Adds all the elements of the collection to the end of the list.
     *
     * @param iterable $items The collection to add.
     *
     * @return void
     */
    #[Override]
    public function addAll(iterable $items): void
    {
        $this->insertAll($this->size, $items);
    }

    /**
     * Gets the element at the specified index.
     *
     * @param int $index The zero-based index of the element to return.
     *
     * @return mixed
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    #[Override]
    public function get(int $index): mixed
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        return $this->items[$index];
    }

    /**
     * Sets the element at the specified index.
     *
     * @param int $index The zero-based index of the element to set.
     * @param mixed $item The element to set.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    #[Override]
    public function set(int $index, mixed $item): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        $this->items[$index] = $item;
        $this->version++;
    }

    /**
     * Inserts an element at the specified index.
     *
     * @param int $index The zero-based index at which the element should be inserted. If the index is equal to the
     *     size of the list, the element is added to the end of the list.
     * @param mixed $item The element to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    #[Override]
    public function insert(int $index, mixed $item): void
    {
        if ($index < 0 || $index > $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index > ArrayList::count())', $index),
            );
        }

        for ($i = $this->size; $i > $index; $i--) {
            $this->items[$i] = $this->items[$i - 1];
        }

        $this->items[$index] = $item;
        $this->size++;
        $this->version++;
    }

    /**
     * Inserts all the elements of the collection at the specified index.
     *
     * @param int $index The zero-based index at which the collection should be inserted. If the index is equal to the
     *     size of the list, the element is added to the end of the list.
     * @param iterable $items The collection to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    #[Override]
    public function insertAll(int $index, iterable $items): void
    {
        if ($index < 0 || $index > $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index > ArrayList::count())', $index),
            );
        }

        if ($items instanceof Traversable) {
            $items = iterator_to_array($items, false);
        }

        $length = count($items);
        $newLength = $this->size + $length;

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

        foreach ($items as $value) {
            $this->items[$index++] = $value;
        }

        $this->size = $newLength;
        $this->version++;
    }

    /**
     * Removes the first occurrence of the element from the list.
     *
     * @param mixed $item The element to remove.
     *
     * @return bool True if the element was successfully removed; otherwise false.
     */
    #[Override]
    public function remove(mixed $item): bool
    {
        $index = $this->indexOf($item);
        if ($index > -1) {
            $this->removeAt($index);
            return true;
        }

        return false;
    }

    /**
     * Removes the element at the specified index of the list.
     *
     * @param int $index The zero-based index of the element to remove.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    #[Override]
    public function removeAt(int $index): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        $this->size--;
        for (; $index < $this->size; $index++) {
            $this->items[$index] = $this->items[$index + 1];
        }

        $this->items[$this->size] = null;
        $this->version++;
    }

    /**
     * Removes a range of elements from the list.
     *
     * @param int $index The zero-based index where the range starts.
     * @param int|null $count The number of elements to remove. If $count is less than or equal to zero, nothing will
     *     be removed from the list. If $count is null or greater than the size of the list, all the elements from
     *     $index to the end of the list will be removed.
     *
     * @return int The number of elements removed from the list.
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= ArrayList::count()).
     */
    public function removeRange(int $index, ?int $count = null): int
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        if ($count === null || $count > $this->size) {
            $count = $this->size - $index;
        }

        $removed = 0;
        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $nextIndex = $index + $count;
                $this->items[$index] = $nextIndex < $this->size ? $this->items[$nextIndex] : null;
                $index++;
            }

            $removed = $i;
            $this->size -= $removed;
            assert($this->size >= 0);

            // Clean up
            $size = count($this->items);
            for ($i = $this->size; $i < $size; $i++) {
                $this->items[$i] = null;
            }

            $this->version++;
        }

        return $removed;
    }

    /**
     * Removes the elements that satisfy the specified condition.
     *
     * @param callable $match A callable which accepts one argument and returns true for the elements to remove.
     *
     * @return int The number of elements removed.
     */
    public function removeIf(callable $match): int
    {
        $freeIndex = 0;
        while ($freeIndex < $this->size && !$match($this->items[$freeIndex])) {
            $freeIndex++;
        }

        if ($freeIndex >= $this->size) {
            return 0;
        }

        $current = $freeIndex + 1;
        while ($current < $this->size) {
            while ($current < $this->size && $match($this->items[$current])) {
                $current++;
            }

            if ($current < $this->size) {
                $this->items[$freeIndex++] = $this->items[$current++];
            }
        }

        $result = $this->size - $freeIndex;
        $this->size = $freeIndex;

        // Clean up unused indexes
        $size = count($this->items);
        for ($i = $this->size; $i < $size; $i++) {
            $this->items[$i] = null;
        }

        return $result;
    }

    /**
     * Returns the zero-based index of the first occurrence of the element.
     *
     * @param mixed $item The element to search.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    #[Override]
    public function indexOf(mixed $item): int
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($item === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the zero-based index of the last occurrence of the element.
     *
     * @param mixed $item The element to search.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    #[Override]
    public function lastIndexOf(mixed $item): int
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
            if ($item === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the zero-based index of the element using a binary search algorithm.
     * This method assumes that the list is sorted.
     *
     * @param mixed $item The element to search.
     * @param callable|null $comparator A callable that accepts two arguments and returns an integer. Pass NULL to use
     *     a default comparator.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    public function binarySearch(mixed $item, ?callable $comparator = null): int
    {
        if (!$this->isEmpty()) {
            if ($comparator === null) {
                // Default comparator
                $comparator = fn($a, $b) => $a <=> $b;
            }

            $l = 0;
            $h = $this->size - 1;
            while ($l <= $h) {
                $m = (int) floor(($l + $h) / 2);
                $c = $comparator($item, $this->items[$m]);

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
     * Returns the zero-based index of the first occurrence of the element that satisfies the specified condition.
     *
     * @param callable $match A callable that takes one argument and returns true if the element satisfies the
     *     condition.
     *
     * @return int The index of the element that satisfies the condition; otherwise -1.
     */
    public function findIndex(callable $match): int
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($match($this->items[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the zero-based index of the last occurrence of the element that satisfies the specified condition.
     *
     * @param callable $match A callable that takes one argument and returns true if the element satisfies the
     *     condition.
     *
     * @return int The index of the element that satisfies the condition; otherwise -1.
     */
    public function findLastIndex(callable $match): int
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
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
        $end = $this->size - 1;
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
     * @param callable|null $comparator A callable that takes two arguments and returns an integer determining the sort
     *     order. Pass NULL to use a default comparator.
     *
     * @return void
     */
    public function sort(?callable $comparator = null): void
    {
        $items = $this->items;
        if (count($items) > $this->size) {
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
     * Sorts a range of elements in the list.
     *
     * @param int $index The zero-based index where the range starts.
     * @param int|null $count The number of elements to sort. If $count is less than or equal to zero, nothing will be
     *     sorted. If $count is null or greater than the size of the list, all the elements from $index to the end of
     *     the list will be sorted.
     * @param callable|null $comparator A callable that takes two arguments and returns an integer determining the sort
     *     order. Pass NULL to use a default comparator.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    public function sortRange(int $index, ?int $count = null, ?callable $comparator = null): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        if ($count === null || $count > $this->size) {
            $count = $this->size - $index;
        }

        if ($count > 0) {
            $slice = [];
            $copyIndex = $index;

            for ($i = 0; $i < $count; $i++) {
                $slice[] = $this->items[$index];
                $index++;
            }

            if ($comparator === null) {
                sort($slice, SORT_REGULAR);
            } else {
                usort($slice, $comparator);
            }

            for ($i = 0; $i < $count; $i++) {
                $this->items[$copyIndex] = $slice[$i];
                $copyIndex++;
            }

            $this->version++;
        }
    }

    /**
     * Returns a new ArrayList containing a range of the elements from the list.
     *
     * @param int $index The zero-based index where the range starts.
     * @param int|null $count The number of elements in the range. If $count is less than or equal to zero, nothing
     *     will be copied to the range. If $count is null or greater than the size of the list, all the elements from
     *     $index to the end of the list will be copied.
     *
     * @return ArrayList A shallow copy of a range of elements from the list.
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > ArrayList::count()).
     */
    public function slice(int $index, ?int $count = null): ArrayList
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= ArrayList::count())', $index),
            );
        }

        if ($count === null || $count > $this->size) {
            $count = $this->size - $index;
        }

        $slice = new ArrayList();
        for ($i = 0; $i < $count; $i++) {
            $slice->add($this->items[$index]);
            $index++;
        }

        return $slice;
    }

    /**
     * Returns a new ArrayList that contains the elements that satisfy the specified condition.
     *
     * @param callable $match A callable that takes one argument and returns true if the element satisfies the
     *     condition.
     *
     * @return ArrayList A shallow copy of the elements that satisfy the condition.
     */
    public function filter(callable $match): ArrayList
    {
        $filter = new ArrayList();
        for ($i = 0; $i < $this->size; $i++) {
            $value = $this->items[$i];
            if ($match($value)) {
                $filter->add($value);
            }
        }

        return $filter;
    }

    /**
     * Returns a new ArrayList that contains all the elements of the list after applying a callback to each of them.
     *
     * @param callable $callback A callable that takes one argument and returns a value that will be copied to the list.
     *
     * @return ArrayList The list that contains the elements.
     */
    public function map(callable $callback): ArrayList
    {
        $map = new ArrayList();
        for ($i = 0; $i < $this->size; $i++) {
            $value = $callback($this->items[$i]);
            $map->add($value);
        }

        return $map;
    }

    /**
     * Performs the specified action on each element of the list.
     *
     * @param callable $action A callable that takes one argument.
     *
     * @return void
     * @throws InvalidOperationException if the list was modified within the callback function.
     */
    public function forEach(callable $action): void
    {
        $version = $this->version;
        for ($i = 0; $i < $this->size; $i++) {
            if ($version !== $this->version) {
                throw new InvalidOperationException('List was modified');
            }

            $action($this->items[$i]);
        }
    }

    /**
     * Returns true if all elements in the list satisfy the specified condition.
     *
     * @param callable $match A callable that takes one argument and returns true if the element satisfies the
     *     condition.
     *
     * @return bool
     */
    public function all(callable $match): bool
    {
        for ($i = 0; $i < $this->size; $i++) {
            if (!$match($this->items[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if at least one element in the list satisfies the specified condition.
     *
     * @param callable $match A callable that takes one argument and returns true if the element satisfies the
     *     condition.
     *
     * @return bool
     */
    public function any(callable $match): bool
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($match($this->items[$i])) {
                return true;
            }
        }

        return false;
    }

    #region ArrayAccess methods
    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->size;
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->removeAt($offset);
    }
    #endregion
}
