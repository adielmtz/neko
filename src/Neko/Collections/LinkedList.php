<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use Traversable;
use function assert;
use function sprintf;

/**
 * Represents an ordered collection of elements stored in a doubly linked list.
 */
class LinkedList implements ListCollection
{
    private ?LinkedListNode $head = null;
    private int $length = 0;
    private int $version = 0;

    /**
     * LinkedList constructor.
     *
     * @param iterable|null $items
     *
     * @throws InvalidOperationException
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
     * @return void
     * @throws InvalidOperationException
     */
    public function __clone(): void
    {
        // Keep a reference to the head of the list
        $head = $this->head;

        // Clean up this cloned instance
        $this->head = null;
        $this->length = 0;
        $this->version = 0;

        // Copy the values
        $node = $head;
        if ($node !== null) {
            do {
                $value = $node->value;
                $this->addLast($value);
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
     * Returns an iterator over the elements in the list.
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
                    throw new InvalidOperationException('List was modified');
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
     * Adds an element to the end of the list.
     *
     * @param mixed $value The element to add to the list.
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function add(mixed $value): void
    {
        $this->addLast($value);
    }

    /**
     * Adds an element to the head of the list.
     *
     * @param mixed $value The element to add to the list.
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function addFirst(mixed $value): void
    {
        $node = new LinkedListNode($this, $value);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->previous, $node);
            $this->head = $node;
        }
    }

    /**
     * Adds an element to the tail of the list.
     *
     * @param mixed $value The element to add to the list.
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function addLast(mixed $value): void
    {
        $node = new LinkedListNode($this, $value);
        if ($this->head === null) {
            $this->insertNodeOnEmptyList($node);
        } else {
            $this->insertNodeAfter($this->head->previous, $node);
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
        return $this->getNodeAt($index)->value;
    }

    /**
     * Returns the first node of the list or null if the list is empty.
     *
     * @return LinkedListNode|null
     */
    public function getFirst(): ?LinkedListNode
    {
        return $this->head;
    }

    /**
     * Returns the last node of the list or null if the list is empty.
     *
     * @return LinkedListNode|null
     */
    public function getLast(): ?LinkedListNode
    {
        return $this->head?->previous;
    }

    /**
     * Returns the node at the specified index in the list.
     *
     * @param int $index The zero-based index of the node to return.
     *
     * @return LinkedListNode
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function getNodeAt(int $index): LinkedListNode
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= LinkedList::count())', $index)
            );
        }

        $node = $this->head;
        do {
            if ($index === 0) {
                break;
            }

            $node = $node->next;
            $index--;
        } while ($node !== $this->head);

        return $node;
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
        $this->getNodeAt($index)->value = $value;
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
     * @throws InvalidOperationException
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index > LinkedList::count()).
     */
    public function insert(int $index, mixed $value): void
    {
        if ($index === $this->length) {
            $this->addLast($value);
        } else {
            $node = $this->getNodeAt($index);
            $newNode = new LinkedListNode($this, $value);
            $this->insertNodeAfter($node->previous, $newNode);

            if ($index === 0) {
                $this->head = $newNode;
            }
        }
    }

    /**
     * Inserts an element after an existing node in the list.
     *
     * @param LinkedListNode $node The node after which to insert the element.
     * @param mixed $value The element to insert.
     *
     * @return void
     * @throws InvalidOperationException if the node does not belong to the list.
     */
    public function insertAfter(LinkedListNode $node, mixed $value): void
    {
        $this->insertNodeAfter($node, new LinkedListNode($this, $value));
    }

    /**
     * Removes the first occurrence of an element in the list.
     *
     * @param mixed $value The element to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
     * @throws InvalidOperationException
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
     * @throws InvalidOperationException
     * @throws OutOfBoundsException if the index is out of range ($index < 0 || $index >= LinkedList::count()).
     */
    public function removeAt(int $index): void
    {
        $node = $this->getNodeAt($index);
        $this->removeNode($node);
    }

    /**
     * Removes the first node from the list.
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function removeFirst(): void
    {
        if ($this->head !== null) {
            $this->removeNode($this->head);
        }
    }

    /**
     * Removes the last node from the list.
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function removeLast(): void
    {
        if ($this->head !== null) {
            $this->removeNode($this->head->previous);
        }
    }

    /**
     * Removes the specified node from the list.
     *
     * @param LinkedListNode $node The node to remove.
     *
     * @return void
     * @throws InvalidOperationException if the list is empty or the node does not belong to the list.
     */
    public function removeNode(LinkedListNode $node): void
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Linked List is empty');
        }

        if ($node->owner !== $this) {
            throw new InvalidOperationException('Node does not belong to this linked list');
        }

        if ($node->next === $node) {
            $this->head = null;
        } else {
            $node->next->previous = $node->previous;
            $node->previous->next = $node->next;
            if ($node === $this->head) {
                $this->head = $node->next;
            }
        }

        $node->detach();
        $this->length--;
        $this->version++;
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
        $node = $this->head;
        $index = 0;
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
     * Returns the zero-based index of the last occurrence of the element in the list.
     *
     * @param mixed $value The element to search.
     *
     * @return int The zero-based index of the last occurrence of the element or -1 if the list does not
     * contain the element.
     */
    public function lastIndexOf(mixed $value): int
    {
        $node = $this->head?->previous;
        $index = $this->length - 1;
        if ($node !== null) {
            do {
                if ($node->value === $value) {
                    return $index;
                }

                $node = $node->previous;
                $index--;
            } while ($node !== $this->head);
        }

        return -1;
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
     * Inserts a node when the list is empty.
     *
     * @param LinkedListNode $node
     *
     * @return void
     * @throws InvalidOperationException if the node does not belong to the list.
     */
    private function insertNodeOnEmptyList(LinkedListNode $node): void
    {
        assert($this->isEmpty() && $this->head === null);
        if ($node->owner !== $this) {
            throw new InvalidOperationException('Node belongs to a different list');
        }

        $node->next = $node;
        $node->previous = $node;
        $this->head = $node;
        $this->length++;
        $this->version++;
    }

    /**
     * Inserts a node after an existing node in the list.
     *
     * @param LinkedListNode $ref The node after which to insert the element.
     * @param LinkedListNode $node The node to insert.
     *
     * @return void
     * @throws InvalidOperationException if any of the given nodes does not belong to the list.
     */
    private function insertNodeAfter(LinkedListNode $ref, LinkedListNode $node): void
    {
        if ($ref->owner !== $this || $node->owner !== $this) {
            throw new InvalidOperationException('Node belongs to a different list');
        }

        $node->next = $ref->next;
        $node->previous = $ref;
        $ref->next->previous = $node;
        $ref->next = $node;
        $this->length++;
        $this->version++;
    }
}
