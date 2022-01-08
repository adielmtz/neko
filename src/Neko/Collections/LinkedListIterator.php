<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use SeekableIterator;

/**
 * Iterates through the nodes of a linked list.
 */
final class LinkedListIterator implements SeekableIterator
{
    private ?LinkedListNode $head;
    private ?LinkedListNode $node;
    private int $length;
    private int $index = 0;

    private int $list_version;
    private int $current_version;

    public function __construct(?LinkedListNode $head, int $length, int &$version)
    {
        $this->head = $head;
        $this->node = $head;
        $this->length = $length;
        $this->list_version = &$version;
        $this->current_version = $version;
    }

    /**
     * @throws InvalidOperationException
     * @throws OutOfBoundsException
     */
    public function seek(mixed $offset): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        if ($offset < 0 || $offset >= $this->length) {
            throw new OutOfBoundsException('Index must be greater than or equal to zero and less than the length of the list');
        }

        $this->index = $offset;
        $node = $this->head;
        do {
            if ($offset === 0) {
                break;
            }

            $node = $node->getNext();
            $offset--;
        } while ($node !== $this->head);

    }

    public function current(): mixed
    {
        return $this->node?->getValue();
    }

    /**
     * @throws InvalidOperationException
     */
    public function next(): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        $this->node = $this->node?->getNext();
        $this->index++;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        if ($this->head !== null) {
            return $this->index === 0 || $this->node !== $this->head;
        }

        return false;
    }

    /**
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        $this->node = $this->head;
        $this->index = 0;
    }
}
