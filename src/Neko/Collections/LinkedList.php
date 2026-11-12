<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use Override;
use Traversable;
use TypeError;
use function assert;
use function count;
use function is_int;

/**
 * Represents a circular, doubly linked list of items that can be accessed by index.
 */
final class LinkedList implements ListCollection
{
    private ?LinkedListNode $head = null;
    private int $size = 0;
    private int $version = 0;

    /**
     * LinkedList constructor.
     *
     * @param iterable|null $items If not null, the items to initialize the list with.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->addAll($items);
        }
    }

    /**
     * Handles clone operator.
     *
     * @return void
     */
    public function __clone(): void
    {
        $head = $this->head;
        $node = $head;
        $this->head = null;
        $this->size = 0;
        $this->version = 0;

        if ($head !== null) {
            do {
                $this->addLast($node->value);
                $node = $node->next;
            } while ($node !== $head);
        }
    }

    /**
     * Returns the data to serialize.
     *
     * @return array
     */
    public function __serialize(): array
    {
        // Serializing the nodes directly recurses once per node, which overflows the stack on long lists
        return $this->toArray();
    }

    /**
     * Restores the list from the serialized data.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->head = null;
        $this->size = 0;
        $this->version = 0;

        foreach ($data as $item) {
            $this->addLast($item);
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
        // (Micro) optimization: reduce cycle-collector runs
        $node = $this->head;
        while ($node !== null) {
            $next = $node->next;
            $node->detach();
            $node = $next;
        }

        $this->head = null;
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
        $node = $this->head;

        if ($node !== null) {
            do {
                yield $node->value;
                $node = $node->next;

                if ($version !== $this->version) {
                    throw new ConcurrentMutationException('List was modified');
                }
            } while ($node !== $this->head);
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
        $this->addLast($item);
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
        $node = new LinkedListNode($item);
        if ($this->isEmpty()) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->prev, $node);
        }
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
        return $this->findNodeByIndex($index)->value;
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
        $this->findNodeByIndex($index)->value = $item;
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

        return $this->head->value;
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

        return $this->head->prev->value;
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
        if ($index === $this->size) {
            $this->addLast($item);
            return;
        }

        $ref = $this->findNodeByIndex($index);
        $node = new LinkedListNode($item);
        $this->insertNodeAfter($ref->prev, $node);

        if ($this->head === $ref) {
            $this->head = $node;
        }
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

        // Collect items into nodes
        $nodes = [];
        foreach ($items as $item) {
            $nodes[] = new LinkedListNode($item);
        }

        $size = count($nodes);
        if ($size === 0) {
            return;
        }

        if ($this->isEmpty()) {
            $this->insertNodeOnEmptyList($nodes[0]);
            assert($this->head !== null);

            for ($i = 1; $i < $size; ++$i) {
                $this->insertNodeAfter($this->head->prev, $nodes[$i]);
            }

            return;
        }

        $ref = $index === $this->size
            ? $this->head
            : $this->findNodeByIndex($index);

        for ($i = 0; $i < $size; ++$i) {
            $this->insertNodeAfter($ref->prev, $nodes[$i]);
        }

        if ($index === 0) {
            $this->head = $nodes[0];
        }
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
        $node = $this->findNodeByValue($item);
        if ($node === null) {
            return false;
        }

        $this->removeNode($node);
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
        $node = $this->findNodeByIndex($index);
        $this->removeNode($node);
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

        $item = $this->head->value;
        $this->removeNode($this->head);
        return $item;
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

        $item = $this->head->prev->value;
        $this->removeNode($this->head->prev);
        return $item;
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
        $node = $this->head;
        $index = 0;
        if ($node !== null) {
            do {
                if ($item === $node->value) {
                    return $index;
                }

                $node = $node->next;
                $index++;
            } while ($node !== $this->head);
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
        $node = $this->head?->prev;
        $index = $this->size - 1;
        if ($node !== null) {
            do {
                if ($item === $node->value) {
                    return $index;
                }

                $node = $node->prev;
                $index--;
            } while ($node !== $this->head->prev);
        }

        return -1;
    }

    #[Override]
    public function reverse(): void
    {
        if ($this->size === 0 || $this->size === 1) {
            return;
        }

        $node = $this->head;
        do {
            // Swap next and prev
            $temp = $node->next;
            $node->next = $node->prev;
            $node->prev = $temp;

            // Move to the next node in the original list
            $node = $node->prev;
        } while ($node !== $this->head);

        // Update head
        $this->head = $this->head->next;
        $this->version++;
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
        $node = $this->head;

        if ($node !== null) {
            do {
                $items[] = $node->value;
                $node = $node->next;
            } while ($node !== $this->head);
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
        $list = new LinkedList();
        if ($this->head === null) {
            assert($this->size === 0);
            return $list;
        }

        $node = $this->head->prev;
        do {
            $list->addLast($node->value);
            $node = $node->prev;
        } while ($node !== $this->head->prev);

        return $list;
    }

    private function findNodeByIndex(int $index): LinkedListNode
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index '$index' is out of bounds");
        }

        // Fast paths
        if ($index === 0) {
            return $this->head;
        }

        if ($index === $this->size - 1) {
            return $this->head->prev;
        }

        if ($index < ($this->size >> 1)) {
            $node = $this->head;
            for ($i = 0; $i < $index; $i++) {
                $node = $node->next;
            }
        } else {
            $node = $this->head->prev;
            for ($i = $this->size - 1; $i > $index; $i--) {
                $node = $node->prev;
            }
        }

        return $node;
    }

    private function findNodeByValue(mixed $value): ?LinkedListNode
    {
        $node = $this->head;
        if ($node !== null) {
            do {
                if ($value === $node->value) {
                    return $node;
                }

                $node = $node->next;
            } while ($node !== $this->head);
        }

        return null;
    }

    private function insertNodeOnEmptyList(LinkedListNode $node): void
    {
        assert($this->isEmpty());

        $this->head = $node;
        $node->next = $node;
        $node->prev = $node;

        $this->size++;
        $this->version++;
    }

    private function insertNodeAfter(LinkedListNode $ref, LinkedListNode $node): void
    {
        assert(!$this->isEmpty());
        assert($ref !== $node);

        $node->next = $ref->next;
        $node->prev = $ref;

        $node->next->prev = $node;
        $node->prev->next = $node;

        $this->size++;
        $this->version++;
    }

    private function removeNode(LinkedListNode $node): void
    {
        assert(!$this->isEmpty());

        if ($node === $node->next) {
            assert($node === $this->head);
            assert($this->size === 1);
            $this->head = null;
        } else {
            assert($this->size > 1);
            $node->next->prev = $node->prev;
            $node->prev->next = $node->next;
            if ($node === $this->head) {
                $this->head = $node->next;
            }
        }

        $node->detach();
        $this->size--;
        $this->version++;
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
        return is_int($offset) && $offset >= 0 && $offset < $this->size
            && $this->findNodeByIndex($offset)->value !== null;
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
