<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a node in a doubly linked list.
 */
final class LinkedListNode
{
    public readonly LinkedList $owner;
    public mixed $value;
    public ?LinkedListNode $next = null;
    public ?LinkedListNode $previous = null;

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
     * Detaches the node from the list.
     *
     * @return void
     */
    public function detach(): void
    {
        $this->next = null;
        $this->previous = null;
    }
}
