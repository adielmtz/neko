<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use Override;
use Traversable;
use TypeError;
use function array_is_list;
use function array_pop;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_splice;
use function assert;
use function count;
use function intdiv;
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

        array_splice($this->items, $index, 0, [$item]);
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

        // Copy the items first, so that inserting a list into itself sees its original items
        $items = iterator_to_array($items, preserve_keys: false);
        $count = count($items);
        if ($count === 0) {
            return;
        }

        array_splice($this->items, $index, 0, $items);
        $this->size += $count;
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

        array_splice($this->items, $index, 1);
        $this->size--;
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
        array_splice($this->items, $index, $count);
        $this->size -= $count;
        $this->version++;
        return $count;
    }

    /**
     * Removes all items that match the specified predicate.
     * If the predicate throws an exception, the list is left unchanged.
     *
     * @param callable $predicate The predicate used to determine which items to remove.
     *
     * @return int The number of items removed.
     */
    public function removeIf(callable $predicate): int
    {
        // Collect the kept items first, so that the list is only modified once every item has been tested
        $kept = [];
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                $kept[] = $item;
            }
        }

        $removed = $this->size - count($kept);
        if ($removed === 0) {
            return 0;
        }

        $this->items = $kept;
        $this->size = count($kept);
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

        $result = array_shift($this->items);
        $this->size--;
        $this->version++;
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

        $result = array_pop($this->items);
        $this->size--;
        $this->version++;
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
     * @param callable|null $comparer The comparer used to compare items. It receives the searched item first and
     *     must return a number that is negative, zero, or positive (an int or a float). If null, a default comparer
     *     is used.
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

            // Compare by sign, so that comparers returning floats (e.g. $a - $b) also find a match on 0.0
            if ($cmp < 0) {
                $hi = $mid - 1;
            } else if ($cmp > 0) {
                $lo = $mid + 1;
            } else {
                return $mid;
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
     * @throws OutOfBoundsException If the index is out of bounds.
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

        array_splice($this->items, $index, $count, $items);
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
        $this->items = array_reverse($this->items);
        $this->version++;
    }

    /**
     * Returns a new list containing a subset of the items, starting at the specified index.
     *
     * @param int $index The zero-based index at which to start the slice. If it equals the number of items in the
     *     list, the slice is empty.
     * @param int|null $count The maximum number of items to include. If null, all remaining items are included.
     *     If negative, the slice stops that many items from the end of the list.
     *
     * @return ListCollection
     * @throws OutOfBoundsException If the index is out of bounds.
     */
    public function slice(int $index, ?int $count = null): ListCollection
    {
        if ($index < 0 || $index > $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        $remain = $this->size - $index;
        if ($count === null || $count > $remain) {
            $count = $remain;
        } else if ($count < 0) {
            $count = max(0, $remain + $count);
        }

        return self::fromArray(array_slice($this->items, $index, $count));
    }

    /**
     * Returns a new list containing the items within the specified index range.
     * The range is empty if both indexes are equal.
     *
     * @param int $fromIndex The zero-based starting index of the range (inclusive).
     * @param int $toIndex The zero-based ending index of the range (exclusive).
     *
     * @return ListCollection
     * @throws OutOfBoundsException If either index is out of bounds or the ending index precedes the starting index.
     */
    public function range(int $fromIndex, int $toIndex): ListCollection
    {
        if ($fromIndex < 0 || $fromIndex > $this->size) {
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
        $items = [];
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $items[] = $item;
            }
        }

        return self::fromArray($items);
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
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $transform($item);
        }

        return self::fromArray($items);
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
        return $this->items;
    }

    /**
     * Returns a new list containing the items in reverse order.
     *
     * @return ListCollection A new list with the items in reverse order.
     */
    #[Override]
    public function toReversed(): ListCollection
    {
        return self::fromArray(array_reverse($this->items));
    }

    /**
     * Creates a list that takes the specified array as its storage.
     *
     * @param array $items A list (sequential keys starting at 0) containing the items.
     *
     * @return ArrayList
     */
    private static function fromArray(array $items): ArrayList
    {
        assert(array_is_list($items));

        $list = new ArrayList();
        $list->items = $items;
        $list->size = count($items);
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
