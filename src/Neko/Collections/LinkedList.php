<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use Traversable;
use function assert;

/**
 * Represents a doubly-linked list.
 */
class LinkedList implements IndexedList
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
                $value = $node->getValue();
                $this->addLast($value);
                $node = $node->getNext();
            } while ($node !== $head);
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
     * Removes all nodes from the list.
     */
    public function clear(): void
    {
        $current = $this->head;
        while ($current !== null) {
            $next = $current->getNext();
            $current->detach();
            $current = $next;
        }

        $this->head = null;
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
        $node = $this->head;
        if ($node !== null) {
            do {
                $destination[$index++] = $node->getValue();
                $node = $node->getNext();
            } while ($node !== $this->head);
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
     * Gets an iterator instance for the linked list.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new LinkedListIterator($this->head, $this->length, $this->version);
    }

    /**
     * Returns the number of nodes in the list.
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
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function add(mixed $value): void
    {
        $this->addLast($value);
    }

    /**
     * Adds a value at the head of the list.
     *
     * @param mixed $value
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
            $this->insertNodeAfter($this->head->getPrevious(), $node);
            $this->head = $node;
        }
    }

    /**
     * Adds a value at the end of the list.
     *
     * @param mixed $value
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
            $this->insertNodeAfter($this->head->getPrevious(), $node);
        }
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
        return $this->getNodeAt($index)->getValue();
    }

    /**
     * Gets the first node of the list.
     *
     * @return LinkedListNode|null
     */
    public function getFirst(): ?LinkedListNode
    {
        return $this->head;
    }

    /**
     * Gets the last node of the list.
     *
     * @return LinkedListNode|null
     */
    public function getLast(): ?LinkedListNode
    {
        return $this->head?->getPrevious();
    }

    /**
     * Gets the node at the specified position in the list.
     *
     * @param int $index
     *
     * @return LinkedListNode
     */
    public function getNodeAt(int $index): LinkedListNode
    {
        if ($index < 0 || $index >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        $node = $this->head;
        do {
            if ($index === 0) {
                break;
            }

            $node = $node->getNext();
            $index--;
        } while ($node !== $this->head);

        return $node;
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
        $this->getNodeAt($index)->setValue($value);
        $this->version++;
    }

    /**
     * Inserts a value at the specified position in the list.
     *
     * @param int $index The zero-based index at which the value will be inserted.
     * The index can be the length of the list, in which case it will insert the value at the end.
     * @param mixed $value The value to insert.
     *
     * @throws InvalidOperationException
     * @throws OutOfBoundsException If the index is less than zero or equal or greater than the length of the list.
     */
    public function insert(int $index, mixed $value): void
    {
        if ($index === $this->length) {
            $this->addLast($value);
        } else {
            $node = $this->getNodeAt($index);
            $newNode = new LinkedListNode($this, $value);
            $this->insertNodeAfter($node->getPrevious(), $newNode);

            if ($index === 0) {
                $this->head = $newNode;
            }
        }
    }

    /**
     * Inserts a node after another node.
     *
     * @param LinkedListNode $node The reference node to insert the new node after.
     * @param mixed $value The value to insert.
     *
     * @throws InvalidOperationException If the reference node or the new node belongs to a different linked list.
     */
    public function insertAfter(LinkedListNode $node, mixed $value): void
    {
        $this->insertNodeAfter($node, new LinkedListNode($this, $value));
    }

    /**
     * Removes the first occurrence of the value in the list.
     *
     * @param mixed $value The value to remove.
     *
     * @return bool Returns true if the value was found and removed from the list.
     *
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
     * Removes the value at the specified position in the list.
     *
     * @param int $index The zero-based index of the value to be removed.
     *
     * @throws InvalidOperationException
     * @throws OutOfBoundsException If the index is less than zero or is equal or greater than the length of the list.
     */
    public function removeAt(int $index): void
    {
        $node = $this->getNodeAt($index);
        $this->removeNode($node);
    }

    /**
     * Removes the first node from the list.
     *
     * @throws InvalidOperationException If the linked list is empty.
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
     * @throws InvalidOperationException If the linked list is empty.
     */
    public function removeLast(): void
    {
        if ($this->head !== null) {
            $this->removeNode($this->head->getPrevious());
        }
    }

    /**
     * Removes a node from the linked list.
     *
     * @param LinkedListNode $node The node to remove.
     *
     * @throws InvalidOperationException If the linked list is empty or the node does not belong to the list.
     */
    public function removeNode(LinkedListNode $node): void
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Linked List is empty');
        }

        if ($node->getOwner() !== $this) {
            throw new InvalidOperationException('The node belongs to a different linked list');
        }

        if ($node->getNext() === $node) {
            $this->head = null;
        } else {
            $node->getNext()->setPrevious($node->getPrevious());
            $node->getPrevious()->setNext($node->getNext());
            if ($node === $this->head) {
                $this->head = $node->getNext();
            }
        }

        $node->detach();
        $this->length--;
        $this->version++;
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
        $node = $this->head;
        $index = 0;
        if ($node !== null) {
            do {
                if ($node->getValue() === $value) {
                    return $index;
                }

                $node = $node->getNext();
                $index++;
            } while ($node !== $this->head);
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
        $node = $this->head?->getPrevious();
        $index = $this->length - 1;
        if ($node !== null) {
            do {
                if ($node->getValue() === $value) {
                    return $index;
                }

                $node = $node->getPrevious();
                $index--;
            } while ($node !== $this->head);
        }

        return -1;
    }

    /**
     * @param mixed $value
     *
     * @return LinkedListNode|null
     */
    private function findNodeByValue(mixed $value): ?LinkedListNode
    {
        $node = $this->head;
        if ($node !== null) {
            do {
                if ($node->getValue() === $value) {
                    return $node;
                }

                $node = $node->getNext();
            } while ($node !== $this->head);
        }

        return null;
    }

    /**
     * @param LinkedListNode $node
     *
     * @return void
     * @throws InvalidOperationException
     */
    private function insertNodeOnEmptyList(LinkedListNode $node): void
    {
        assert($this->isEmpty() && $this->head === null);
        if ($node->getOwner() !== $this) {
            throw new InvalidOperationException('Node belongs to a different list');
        }

        $node->setNext($node);
        $node->setPrevious($node);
        $this->head = $node;
        $this->length++;
        $this->version++;
    }

    /**
     * @param LinkedListNode $ref
     * @param LinkedListNode $node
     *
     * @return void
     * @throws InvalidOperationException
     */
    private function insertNodeAfter(LinkedListNode $ref, LinkedListNode $node): void
    {
        if ($ref->getOwner() !== $this || $node->getOwner() !== $this) {
            throw new InvalidOperationException('Node belongs to a different list');
        }

        $node->setNext($ref->getNext());
        $node->setPrevious($ref);
        $ref->getNext()->setPrevious($node);
        $ref->setNext($node);
        $this->length++;
        $this->version++;
    }
}
