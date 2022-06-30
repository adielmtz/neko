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
     * @param iterable|null $items A collection of values that will be copied to the linked list.
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
     * Creates new nodes for the cloned linked list.
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
     * Serializes the linked list.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserializes the linked list.
     *
     * @param array $data
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
     * Returns true if the linked list contains no elements.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all elements from the linked list.
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
     * Returns true if the linked list contains a specific element.
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
     * Copies the elements of the linked list to an array.
     *
     * @param array $array
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
     * Returns an array containing all the elements of the linked list.
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
     * Returns an iterator over the elements in the linked list.
     *
     * @return Traversable
     * @throws InvalidOperationException
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
     * Returns the number of elements in the linked list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Adds an element to the end of the linked list.
     *
     * @param mixed $value The element to add to the linked list.
     *
     * @return void
     */
    public function add(mixed $value): void
    {
        $this->addLast($value);
    }

    /**
     * Adds an element to the head of the linked list.
     *
     * @param mixed $value The element to add to the linked list.
     *
     * @return void
     */
    public function addFirst(mixed $value): void
    {
        $node = new LinkedListNode($value);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->prev, $node);
            $this->head = $node;
        }
    }

    /**
     * Adds an element to the end of the linked list.
     *
     * @param mixed $value The element to add to the linked list.
     *
     * @return void
     */
    public function addLast(mixed $value): void
    {
        $node = new LinkedListNode($value);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->prev, $node);
        }
    }

    /**
     * Returns the element at the specified index.
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
     * Returns the value of the first node in the linked list.
     *
     * @return mixed
     * @throws InvalidOperationException If the linked list is empty.
     */
    public function getFirst(): mixed
    {
        if ($this->head === null) {
            throw new InvalidOperationException('Linked List is empty');
        }

        return $this->head->value;
    }

    /**
     * Returns the value of the last node in the linked list.
     *
     * @return mixed
     * @throws InvalidOperationException If the linked list is empty.
     */
    public function getLast(): mixed
    {
        if ($this->head === null) {
            throw new InvalidOperationException('Linked List is empty');
        }

        return $this->head->prev->value;
    }

    /**
     * Replaces the element at the specified index with a different element.
     *
     * @param int $index The zero-based index of the element to replace.
     * @param mixed $value The new element.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function set(int $index, mixed $value): void
    {
        $this->findNodeByIndex($index)->value = $value;
        $this->version++;
    }

    /**
     * Inserts an element at the specified index.
     *
     * @param int $index The zero-based index at which the element should be inserted.
     * If the index is equal to the size of the list, the element is added to the end of the linked list.
     * @param mixed $value The element to insert.
     *
     * @return void
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > LinkedList::count()).
     */
    public function insert(int $index, mixed $value): void
    {
        if ($index === $this->length) {
            $this->addLast($value);
        } else {
            $reference = $this->findNodeByIndex($index);
            $node = new LinkedListNode($value);
            $this->insertNodeAfter($reference->prev, $node);

            if ($index === 0) {
                $this->head = $node;
            }
        }
    }

    /**
     * Removes the first occurrence of an element in the linked list.
     *
     * @param mixed $value The element to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
     */
    public function remove(mixed $value): bool
    {
        $node = $this->findNodeByValue($value);
        if ($node !== null) {
            $this->removeNode($node);
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
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function removeAt(int $index): void
    {
        $node = $this->findNodeByIndex($index);
        $this->removeNode($node);
    }

    /**
     * Removes the first element from the linked list.
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
     * Removes the last element from the linked list.
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
     * Returns the zero-based index of the first occurrence of the element in the linked list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the first occurrence of the element or -1 if the linked list does not
     * contain the element.
     */
    public function indexOf(mixed $value): int
    {
        $index = 0;
        $node = $this->head;
        if ($node !== null) {
            do {
                if ($node->value === $value) {
                    return $index;
                }

                $node = $node->next;
                $index++;
            } while ($node !== $this->head);
        }

        return -1;
    }

    /**
     * Returns the zero-based index of the last occurrence of the element in the linked list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the last occurrence of the element or -1 if the linked list does not
     * contain the element.
     */
    public function lastIndexOf(mixed $value): int
    {
        $index = $this->length - 1;
        $node = $this->head?->prev;
        if ($node !== null) {
            do {
                if ($node->value === $value) {
                    return $index;
                }

                $node = $node->prev;
                $index--;
            } while ($node !== $this->head);
        }

        return -1;
    }

    /**
     * Removes the specified node from the linked list.
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
     * Returns the node at the specified index.
     *
     * @param int $index The zero-based index of the node to return
     *
     * @return LinkedListNode
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    private function findNodeByIndex(int $index): LinkedListNode
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= LinkedList::count())', $index)
            );
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
     * Returns the first node that contains the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return LinkedListNode|null
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
