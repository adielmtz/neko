<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use Override;
use Traversable;
use TypeError;
use function array_slice;
use function assert;
use function count;
use function intdiv;
use function is_array;
use function is_int;
use function iterator_to_array;
use function max;
use function min;
use function sort;
use function usort;

/**
 * Represents a list of items that can be accessed by index.
 */
final class ArrayList implements ListCollection
{
    private array $items = [];
    private int $size = 0;
    private int $version = 0;

    /**
     * ArrayList constructor.
     *
     * @param iterable|null $items If not null, the items to initialize the list with.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, preserve_keys: false);
            $this->size = count($this->items);
        }
    }

    /**
     * Determines whether the list is empty.
     *
     * @return bool True if the list is empty; otherwise, false.
     */
    #[Override]
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Determines whether the list contains a specific item.
     *
     * @param mixed $item The item to search for.
     *
     * @return bool True if the list contains the item; otherwise, false.
     */
    #[Override]
    public function contains(mixed $item): bool
    {
        return $this->indexOf($item) !== -1;
    }

    /**
     * Determines whether the list contains all specified items.
     *
     * @param iterable $items The iterable containing the items to search for.
     *
     * @return bool True if the list contains all items; otherwise, false.
     */
    #[Override]
    public function containsAll(iterable $items): bool
    {
        foreach ($items as $item) {
            if (!$this->contains($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the number of items in the list.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Removes all items from the list.
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
     * Returns an iterator over the items in the list.
     *
     * @return Traversable
     * @throws ConcurrentMutationException If the list is modified during iteration.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $version = $this->version;
        for ($i = 0; $i < $this->size; ++$i) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new ConcurrentMutationException('List was modified');
            }
        }
    }

    /**
     * Adds an item to the list.
     *
     * @param mixed $item The item to add.
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
     * Appends all items from the specified iterable to the end of the list,
     * preserving the order in which they are yielded.
     *
     * @param iterable $items The iterable containing the items to append.
     *
     * @return void
     */
    #[Override]
    public function addAll(iterable $items): void
    {
        $this->insertAll($this->size, $items);
    }

    /**
     * Adds an item to the beginning of the list.
     *
     * @param mixed $item The item to add.
     *
     * @return void
     */
    #[Override]
    public function addFirst(mixed $item): void
    {
        $this->insert(0, $item);
    }

    /**
     * Adds an item to the end of the list.
     *
     * @param mixed $item The item to add.
     *
     * @return void
     */
    #[Override]
    public function addLast(mixed $item): void
    {
        $this->add($item);
    }

    /**
     * Returns the item at the specified index.
     *
     * @param int $index The zero-based index of the item to retrieve.
     *
     * @return mixed
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    #[Override]
    public function get(int $index): mixed
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        return $this->items[$index];
    }

    /**
     * Replaces the item at the specified index with the given item.
     *
     * @param int $index The zero-based index of the item to replace.
     * @param mixed $item The item to store at the specified index.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    #[Override]
    public function set(int $index, mixed $item): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        $this->items[$index] = $item;
        $this->version++;
    }

    /**
     * Returns the first item in the list.
     *
     * @return mixed
     * @throws InvalidOperationException If the list is empty.
     */
    #[Override]
    public function getFirst(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('List is empty');
        }

        return $this->items[0];
    }

    /**
     * Returns the last item in the list.
     *
     * @return mixed
     * @throws InvalidOperationException If the list is empty.
     */
    #[Override]
    public function getLast(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('List is empty');
        }

        return $this->items[$this->size - 1];
    }

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
    #[Override]
    public function insert(int $index, mixed $item): void
    {
        if ($index < 0 || $index > $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        for ($i = $this->size; $i > $index; --$i) {
            $this->items[$i] = $this->items[$i - 1];
        }

        $this->items[$index] = $item;
        $this->size++;
        $this->version++;
    }

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
    #[Override]
    public function insertAll(int $index, iterable $items): void
    {
        if ($index < 0 || $index > $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        if ($items instanceof Traversable) {
            $items = iterator_to_array($items, preserve_keys: false);
        }

        assert(is_array($items));
        $size = count($items);
        if ($size === 0) {
            return;
        }

        $newSize = $this->size + $size;
        // Preallocate new slots to keep key order
        for ($i = $this->size; $i < $newSize; ++$i) {
            $this->items[$i] = null;
        }

        // == Example ==
        // insert [X,Y,Z] at index 3
        // we have to move the elements from index 3, 3 positions to the right
        // iterating in reverse order.
        //
        // $this->items:
        // initial state -> [A,B,C,D,E,F,G,H, , , , , ]
        // first loop    -> [A,B,C, , , ,D,E,F,G,H, , ]
        // second loop   -> [A,B,C,X,Y,Z,D,E,F,G,H, , ]
        if ($index < $this->size) {
            for ($i = $newSize - 1; $i >= $index + $size; --$i) {
                $this->items[$i] = $this->items[$i - $size];
            }
        }

        foreach ($items as $value) {
            $this->items[$index++] = $value;
        }

        $this->size = $newSize;
        $this->version++;
    }

    /**
     * Removes one occurrence of the specified item from the list, if present.
     *
     * @param mixed $item The item to remove.
     *
     * @return bool True if an item was removed; otherwise, false.
     */
    #[Override]
    public function remove(mixed $item): bool
    {
        $index = $this->indexOf($item);
        if ($index === -1) {
            return false;
        }

        $this->removeAt($index);
        return true;
    }

    /**
     * Removes the item at the specified index.
     *
     * @param int $index The zero-based index of the item to remove.
     *
     * @return void
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    #[Override]
    public function removeAt(int $index): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        $this->size--;
        while ($index < $this->size) {
            $this->items[$index] = $this->items[$index + 1];
            $index++;
        }

        $this->items[$this->size] = null;
        $this->version++;
    }

    /**
     * Removes a range of items from the list, starting at the specified index.
     *
     * @param int $index The zero-based starting index of the range.
     * @param int|null $count The maximum number of items to remove. If null, all remaining items are removed.
     *
     * @return int The number of items removed.
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function removeRange(int $index, ?int $count = null): int
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        if ($count === null) {
            $count = $this->size - $index;
        }

        if ($count <= 0) {
            return 0;
        }

        $count = min($count, $this->size - $index);
        $size = $this->size - $count;

        // Shift the remaining items to the left
        for ($i = $index; $i < $size; ++$i) {
            $this->items[$i] = $this->items[$i + $count];
        }

        // Clear removed slot & references
        for ($i = $size; $i < $this->size; ++$i) {
            $this->items[$i] = null;
        }

        $this->size = $size;
        $this->version++;
        return $count;
    }

    /**
     * Removes all items that match the specified predicate.
     *
     * @param callable $predicate The predicate used to determine which items to remove.
     *
     * @return int The number of items removed.
     */
    public function removeIf(callable $predicate): int
    {
        $writeIndex = 0;
        $removed = 0;

        for ($readIndex = 0; $readIndex < $this->size; ++$readIndex) {
            $item = $this->items[$readIndex];
            if ($predicate($item)) {
                $removed++;
                continue;
            }

            if ($writeIndex !== $readIndex) {
                $this->items[$writeIndex] = $item;
            }

            $writeIndex++;
        }

        if ($removed === 0) {
            return 0;
        }

        // Clear removed slot & references
        for ($i = $writeIndex; $i < $this->size; ++$i) {
            $this->items[$i] = null;
        }

        $this->size = $writeIndex;
        $this->version++;
        return $removed;
    }

    /**
     * Removes and returns the first item in the list.
     *
     * @return mixed The removed item.
     * @throws InvalidOperationException If the list is empty.
     */
    #[Override]
    public function removeFirst(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('List is empty');
        }

        $result = $this->items[0];
        $this->removeAt(0);
        return $result;
    }

    /**
     * Removes and returns the last item in the list.
     *
     * @return mixed The removed item.
     * @throws InvalidOperationException If the list is empty.
     */
    #[Override]
    public function removeLast(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('List is empty');
        }

        $result = $this->items[$this->size - 1];
        $this->removeAt($this->size - 1);
        return $result;
    }

    /**
     * Returns the index of the first occurrence of the specified item, or -1 if the item is not found.
     *
     * @param mixed $item The item to search for.
     *
     * @return int
     */
    #[Override]
    public function indexOf(mixed $item): int
    {
        for ($i = 0; $i < $this->size; ++$i) {
            if ($item === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the index of the last occurrence of the specified item, or -1 if the item is not found.
     *
     * @param mixed $item The item to search for.
     *
     * @return int
     */
    #[Override]
    public function lastIndexOf(mixed $item): int
    {
        for ($i = $this->size - 1; $i >= 0; --$i) {
            if ($item === $this->items[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the index of the specified item using a binary search algorithm, or -1 if the item is not found.
     * This method assumes that the list is sorted.
     *
     * @param mixed $item The item to search for.
     * @param callable|null $comparer The comparer used to compare items. If null, a default comparer is used.
     *
     * @return int
     */
    public function binarySearch(mixed $item, ?callable $comparer = null): int
    {
        if ($this->isEmpty()) {
            return -1;
        }

        if ($comparer === null) {
            $comparer = fn($a, $b) => $a <=> $b;
        }

        $lo = 0;
        $hi = $this->size - 1;

        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            $cmp = $comparer($item, $this->items[$mid]);

            if ($cmp === 0) {
                return $mid;
            } else {
                if ($cmp < 0) {
                    $hi = $mid - 1;
                } else {
                    $lo = $mid + 1;
                }
            }
        }

        return -1;
    }

    /**
     * Returns the index of the first item that matches the specified predicate, or -1 if no matching item is found.
     *
     * @param callable $predicate The predicate used to test each item.
     *
     * @return int
     */
    public function findIndex(callable $predicate): int
    {
        for ($i = 0; $i < $this->size; ++$i) {
            if ($predicate($this->items[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns the index of the last item that matches the specified predicate, or -1 if no matching item is found.
     *
     * @param callable $predicate The predicate used to test each item.
     *
     * @return int
     */
    public function findLastIndex(callable $predicate): int
    {
        for ($i = $this->size - 1; $i >= 0; --$i) {
            if ($predicate($this->items[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Sorts the items in the list.
     *
     * @param callable|null $comparer The comparer used to compare items. If null, a default comparer is used.
     *
     * @return void
     */
    public function sort(?callable $comparer = null): void
    {
        if ($this->size < 2) {
            return;
        }

        if (count($this->items) > $this->size) {
            // Array contains unused slots, so we unset() them before sorting.
            $size = count($this->items);
            for ($i = $this->size; $i < $size; ++$i) {
                unset($this->items[$i]);
            }
        }

        if ($comparer === null) {
            sort($this->items);
        } else {
            usort($this->items, $comparer);
        }

        $this->version++;
    }

    /**
     * Sorts the items within the specified range.
     *
     * @param int $index The zero-based starting index of the range.
     * @param int|null $count The maximum number of items to sort. If null, all remaining items are sorted.
     * @param callable|null $comparer The comparer used to compare items. If null, a default comparer is used.
     *
     * @return void
     */
    public function sortRange(int $index, ?int $count = null, ?callable $comparer = null): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        if ($count === null) {
            $count = $this->size - $index;
        }

        if ($count < 2) {
            return;
        }

        $count = min($count, $this->size - $index);
        $items = array_slice($this->items, $index, $count);

        if ($comparer === null) {
            sort($items);
        } else {
            usort($items, $comparer);
        }

        // Copy items back into the array
        for ($i = 0; $i < $count; ++$i) {
            $this->items[$index + $i] = $items[$i];
        }

        $this->version++;
    }

    /**
     * Reverses the order of items in the list.
     *
     * @return void
     */
    #[Override]
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
     * Returns a new list containing a subset of the items, starting at the specified index.
     *
     * @param int $index The zero-based index at which to start the slice.
     * @param int|null $count The maximum number of items to include. If null, all remaining items are included.
     * If negative, the slice stops that many items from the end of the list.
     *
     * @return ListCollection
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function slice(int $index, ?int $count = null): ListCollection
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        $remain = $this->size - $index;
        if ($count === null || $count > $remain) {
            $count = $remain;
        } else if ($count < 0) {
            $count = max(0, $remain + $count);
        }

        $list = new ArrayList();
        $list->items = array_slice($this->items, $index, $count);
        $list->size = count($list->items);
        return $list;
    }

    /**
     * Returns a new list containing the items within the specified index range.
     *
     * @param int $fromIndex The zero-based starting index of the range.
     * @param int $toIndex The zero-based ending index of the range (exclusive).
     *
     * @return ListCollection
     * @throws OutOfBoundsException If either index is out of bounds or the ending index precedes the starting index.
     */
    public function range(int $fromIndex, int $toIndex): ListCollection
    {
        if ($fromIndex < 0 || $fromIndex >= $this->size) {
            throw new OutOfBoundsException("Index (\$fromIndex) '$fromIndex' is out of bounds");
        }

        if ($toIndex < $fromIndex || $toIndex > $this->size) {
            throw new OutOfBoundsException("Index (\$toIndex) '$toIndex' is out of bounds");
        }

        return $this->slice($fromIndex, $toIndex - $fromIndex);
    }

    /**
     * Returns a new list containing all items that match the specified predicate.
     *
     * @param callable $predicate The predicate used to determine which items to include.
     *
     * @return ListCollection
     */
    public function filter(callable $predicate): ListCollection
    {
        $list = new ArrayList();
        for ($i = 0; $i < $this->size; ++$i) {
            if ($predicate($this->items[$i])) {
                $list->add($this->items[$i]);
            }
        }

        return $list;
    }

    /**
     * Returns a new list containing the results of applying the specified transformation to each item.
     *
     * @param callable $transform The transformation to apply to each item.
     *
     * @return ListCollection
     */
    public function map(callable $transform): ListCollection
    {
        $list = new ArrayList();
        for ($i = 0; $i < $this->size; ++$i) {
            $item = $this->items[$i];
            $mapped = $transform($item);
            $list->add($mapped);
        }

        return $list;
    }

    /**
     * Performs the specified action on each item in the list.
     *
     * @param callable $action The action to perform on each item.
     *
     * @return void
     * @throws ConcurrentMutationException If the list is modified during iteration.
     */
    public function forEach(callable $action): void
    {
        foreach ($this as $item) {
            $action($item);
        }
    }

    /**
     * Determines whether all items in the list match the specified predicate.
     *
     * @param callable $predicate The predicate used to test each item.
     *
     * @return bool True if all items match the predicate; otherwise, false.
     */
    public function all(callable $predicate): bool
    {
        for ($i = 0; $i < $this->size; ++$i) {
            if (!$predicate($this->items[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether any item in the list matches the specified predicate.
     *
     * @param callable $predicate The predicate used to test each item.
     *
     * @return bool True if at least one item matches the predicate; otherwise, false.
     */
    public function any(callable $predicate): bool
    {
        for ($i = 0; $i < $this->size; ++$i) {
            if ($predicate($this->items[$i])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array containing all items in the list.
     *
     * @return array
     */
    #[Override]
    public function toArray(): array
    {
        $items = [];
        for ($i = 0; $i < $this->size; ++$i) {
            $items[] = $this->items[$i];
        }

        return $items;
    }

    /**
     * Returns a new list containing the items in reverse order.
     *
     * @return ListCollection A new list with the items in reverse order.
     */
    #[Override]
    public function toReversed(): ListCollection
    {
        $list = new ArrayList();
        $items = [];

        for ($i = $this->size - 1; $i >= 0; --$i) {
            $items[] = $this->items[$i];
        }

        $list->items = $items;
        $list->size = $this->size;
        return $list;
    }

    #region Array Access implementation
    /**
     * Determines whether the specified index exists and holds a non-null item,
     * matching the behavior of isset() on arrays.
     *
     * @param mixed $offset The zero-based index to check.
     *
     * @return bool
     */
    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && isset($this->items[$offset]);
    }

    /**
     * Returns the item at the specified index.
     *
     * The item is returned by value, so modifying a stored array in place (e.g. $list[0][] = $x) has no effect.
     * Read the item, modify it, and assign it back instead.
     *
     * @param mixed $offset The zero-based index of the item to retrieve.
     *
     * @return mixed
     * @throws TypeError If the offset is not an integer.
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset)) {
            throw new TypeError('Argument #1 ($offset) must be of type int');
        }

        return $this->get($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
            return;
        }

        if (!is_int($offset)) {
            throw new TypeError('Argument #1 ($offset) must be of type int');
        }

        $this->set($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        if (!is_int($offset)) {
            throw new TypeError('Argument #1 ($offset) must be of type int');
        }

        $this->removeAt($offset);
    }
    #endregion
}
