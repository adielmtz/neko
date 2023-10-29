<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use Traversable;
use function assert;
use function sprintf;

/**
 * Represents an ordered collection of elements stored in a doubly linked list.
 */
class LinkedList implements ArrayAccess, ListCollection
{
    private ?LinkedListNode $head = null;
    private int $length = 0;
    private int $version = 0;

    /**
     * LinkedList constructor.
     *
     * @param iterable|null $items A collection of initial elements that will be copied to the list.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            foreach ($items as $value) {
                $this->addLast($value);
            }
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
        $this->head = null;
        $this->length = 0;
        $this->version = 0;

        $node = $head;
        if ($node !== null) {
            do {
                $this->addLast($node->value);
                $node = $node->next;
            } while ($node !== $head);
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
        foreach ($data as $value) {
            $this->addLast($value);
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
        $current = $this->head;
        while ($current !== null) {
            $next = $current->next;
            $current->detach();
            $current = $next;
        }

        $this->head = null;
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the list contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
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
    public function copyTo(array &$array, int $index = 0): void
    {
        $node = $this->head;
        if ($node !== null) {
            do {
                $array[$index++] = $node->value;
                $node = $node->next;
            } while ($node !== $this->head);
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
     * Gets an iterator that can traverse through the elements of the list.
     *
     * @return Traversable
     * @throws InvalidOperationException if the list was modified within the iterator.
     */
    public function getIterator(): Traversable
    {
        $version = $this->version;
        $node = $this->head;
        if ($node !== null) {
            do {
                yield $node->value;
                $node = $node->next;

                if ($version !== $this->version) {
                    throw new InvalidOperationException('Linked List was modified');
                }
            } while ($node !== $this->head);
        }
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
     * Adds the element to the end of the list.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    public function add(mixed $item): void
    {
        $this->addLast($item);
    }

    /**
     * Adds all the elements of the collection to the end of the list.
     *
     * @param iterable $items The collection to add.
     *
     * @return void
     */
    public function addAll(iterable $items): void
    {
        foreach ($items as $value) {
            $this->addLast($value);
        }
    }

    /**
     * Adds the element to the top of the list.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    public function addFirst(mixed $item): void
    {
        $node = new LinkedListNode($item);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->prev, $node);
            $this->head = $node;
        }
    }

    /**
     * Adds the element to the end of the list.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    public function addLast(mixed $item): void
    {
        $node = new LinkedListNode($item);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->prev, $node);
        }
    }

    /**
     * Gets the element at the specified index.
     *
     * @param int $index The zero-based index of the element to return.
     *
     * @return mixed
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function get(int $index): mixed
    {
        $node = $this->findNodeByIndex($index);
        return $node->value;
    }

    /**
     * Gets the element located at the top of the list.
     *
     * @return mixed
     * @throws InvalidOperationException if the list is empty.
     */
    public function getFirst(): mixed
    {
        if ($this->head === null) {
            throw new InvalidOperationException('Linked List is empty');
        }

        return $this->head->value;
    }

    /**
     * Gets the element located at the end of the list.
     *
     * @return mixed
     * @throws InvalidOperationException if the list is empty.
     */
    public function getLast(): mixed
    {
        if ($this->head === null) {
            throw new InvalidOperationException('Linked List is empty');
        }

        return $this->head->prev->value;
    }

    /**
     * Sets the element at the specified index.
     *
     * @param int $index The zero-based index of the element to set.
     * @param mixed $item The element to set.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function set(int $index, mixed $item): void
    {
        $this->findNodeByIndex($index)->value = $item;
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
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > LinkedList::count()).
     */
    public function insert(int $index, mixed $item): void
    {
        if ($index === $this->length) {
            $this->addLast($item);
        } else {
            $reference = $this->findNodeByIndex($index);
            $node = new LinkedListNode($item);
            $this->insertNodeAfter($reference->prev, $node);

            if ($index === 0) {
                $this->head = $node;
            }
        }
    }

    /**
     * Inserts all the elements of the collection at the specified index.
     *
     * @param int $index The zero-based index at which the collection should be inserted. If the index is equal to the
     *     size of the list, the element is added to the end of the list.
     * @param iterable $items The collection to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > LinkedList::count()).
     */
    public function insertAll(int $index, iterable $items): void
    {
        if ($index === $this->length) {
            $this->addAll($items);
        } else {
            $prev = $this->findNodeByIndex($index)->prev;
            $next = $prev->next;

            foreach ($items as $value) {
                $node = new LinkedListNode($value);
                $prev->next = $node;
                $node->prev = $prev;
                $node->next = $next;
                $next->prev = $node;
                $prev = $node;
                $this->length++;

                if ($index === 0) {
                    $this->head = $node;
                    $index++;
                }
            }

            $this->version++;
        }
    }

    /**
     * Removes the first occurrence of the element from the list.
     *
     * @param mixed $item The element to remove.
     *
     * @return bool True if the element was successfully removed; otherwise false.
     */
    public function remove(mixed $item): bool
    {
        $node = $this->findNodeByValue($item);
        if ($node !== null) {
            $this->removeNode($node);
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
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function removeAt(int $index): void
    {
        $node = $this->findNodeByIndex($index);
        $this->removeNode($node);
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
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function removeRange(int $index, ?int $count = null): int
    {
        $node = $this->findNodeByIndex($index);
        if ($count === null || $count > $this->length) {
            $count = $this->length - $index;
        }

        for ($i = 0; $i < $count; $i++) {
            $next = $node->next;
            $this->removeNode($node);
            $node = $next;
        }

        return $i;
    }

    /**
     * Removes the element located at the top of the list.
     *
     * @return void
     */
    public function removeFirst(): void
    {
        if ($this->head !== null) {
            $this->removeNode($this->head);
        }
    }

    /**
     * Removes the element located at the end of the list.
     *
     * @return void
     */
    public function removeLast(): void
    {
        if ($this->head !== null) {
            $this->removeNode($this->head->prev);
        }
    }

    /**
     * Returns the zero-based index of the first occurrence of the element.
     *
     * @param mixed $item The element to search.
     *
     * @return int The index of the element if found in the list; otherwise -1.
     */
    public function indexOf(mixed $item): int
    {
        $index = 0;
        $node = $this->head;
        if ($node !== null) {
            do {
                if ($node->value === $item) {
                    return $index;
                }

                $node = $node->next;
                $index++;
            } while ($node !== $this->head);
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
    public function lastIndexOf(mixed $item): int
    {
        $index = $this->length - 1;
        $node = $this->head?->prev;
        if ($node !== null) {
            do {
                if ($node->value === $item) {
                    return $index;
                }

                $node = $node->prev;
                $index--;
            } while ($node !== $this->head);
        }

        return -1;
    }

    /**
     * Removes the node from the linked list.
     *
     * @param LinkedListNode $node The node to remove.
     *
     * @return void
     */
    private function removeNode(LinkedListNode $node): void
    {
        if ($node->next === $node) {
            $this->head = null;
        } else {
            $node->next->prev = $node->prev;
            $node->prev->next = $node->next;
            if ($node === $this->head) {
                $this->head = $node->next;
            }
        }

        $node->detach();
        $this->length--;
        $this->version++;
    }

    /**
     * Returns the node located at the specified index.
     *
     * @param int $index The zero-based index of the node to return.
     *
     * @return LinkedListNode
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    private function findNodeByIndex(int $index): LinkedListNode
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= LinkedList::count())', $index),
            );
        }

        // quick optimization
        if ($index === 0) {
            return $this->head;
        } else if ($index === $this->length - 1) {
            return $this->head->prev;
        }

        if ($index < ($this->length >> 1)) {
            $node = $this->head;
            for ($i = 0; $i < $index; $i++) {
                $node = $node->next;
            }
        } else {
            $node = $this->head->prev;
            for ($i = $this->length - 1; $i > $index; $i--) {
                $node = $node->prev;
            }
        }

        return $node;
    }

    /**
     * Returns the first node that contains the specified value.
     *
     * @param mixed $value The value to search.
     *
     * @return LinkedListNode|null The first occurrence of the node that contains the value; otherwise NULL.
     */
    private function findNodeByValue(mixed $value): ?LinkedListNode
    {
        $node = $this->head;
        if ($node !== null) {
            do {
                if ($node->value === $value) {
                    return $node;
                }

                $node = $node->next;
            } while ($node !== $this->head);
        }

        return null;
    }

    /**
     * Inserts a node when the linked list is empty.
     *
     * @param LinkedListNode $node The node to insert.
     *
     * @return void
     */
    private function insertNodeOnEmptyList(LinkedListNode $node): void
    {
        assert($this->length === 0 && $this->head === null);
        $node->next = $node;
        $node->prev = $node;
        $this->head = $node;
        $this->length++;
        $this->version++;
    }

    /**
     * Inserts a node after the reference node.
     *
     * @param LinkedListNode $ref The reference node.
     * @param LinkedListNode $node The node to insert.
     *
     * @return void
     */
    private function insertNodeAfter(LinkedListNode $ref, LinkedListNode $node): void
    {
        $node->next = $ref->next;
        $node->prev = $ref;
        $node->next->prev = $node;
        $ref->next = $node;
        $this->length++;
        $this->version++;
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
