<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a node in a doubly linked list.
 */
final class LinkedListNode
{
    public mixed $value;
    public ?LinkedListNode $next = null;
    public ?LinkedListNode $prev = null;

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
        $this->next = null;
        $this->prev = null;
    }
}
