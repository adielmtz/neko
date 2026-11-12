<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a node in a doubly linked list.
 */
final class LinkedListNode
{
    public ?LinkedListNode $next = null;
    public ?LinkedListNode $prev = null;
    public mixed $value;

    /**
     * LinkedListNode constructor.
     *
     * @param mixed|null $value The value of the node.
     */
    public function __construct(mixed $value = null)
    {
        $this->value = $value;
    }

    /**
     * Removes the next and previous nodes.
     *
     * @return void
     */
    public function detach(): void
    {
        $this->prev = null;
        $this->next = null;
    }
}
