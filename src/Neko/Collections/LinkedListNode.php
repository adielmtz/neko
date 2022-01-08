<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a node in a LinkedList.
 */
final class LinkedListNode
{
    private ?LinkedList $owner;
    private ?LinkedListNode $next = null;
    private ?LinkedListNode $prev = null;
    private mixed $value;

    /**
     * LinkedListNode constructor.
     *
     * @param LinkedList $owner The linked list that the node belongs to.
     */
    public function __construct(LinkedList $owner, mixed $value = null)
    {
        $this->owner = $owner;
        $this->value = $value;
    }

    /**
     * Gets the linked list that the node belongs to.
     *
     * @return LinkedList|null
     */
    public function getOwner(): ?LinkedList
    {
        return $this->owner;
    }

    /**
     * Gets the next node.
     *
     * @return LinkedListNode|null
     */
    public function getNext(): ?LinkedListNode
    {
        return $this->next;
    }

    /**
     * Sets the next node.
     *
     * @param LinkedListNode|null $next
     */
    public function setNext(?LinkedListNode $next): void
    {
        $this->next = $next;
    }

    /**
     * Gets the previous node.
     *
     * @return LinkedListNode|null
     */
    public function getPrevious(): ?LinkedListNode
    {
        return $this->prev;
    }

    /**
     * Sets the previous node.
     *
     * @param LinkedListNode|null $previous
     */
    public function setPrevious(?LinkedListNode $previous): void
    {
        $this->prev = $previous;
    }

    /**
     * Gets the value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sets the value.
     *
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Detaches the node from the list.
     */
    public function detach(): void
    {
        $this->owner = null;
        $this->next = null;
        $this->prev = null;
    }
}
