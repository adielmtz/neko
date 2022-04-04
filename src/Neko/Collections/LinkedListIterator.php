<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use SeekableIterator;

/**
 * Iterates over the nodes of a doubly linked list.
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
     * @param mixed $offset
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function seek(mixed $offset): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('List was modified');
        }

        if ($offset < 0 || $offset >= $this->length) {
            throw new OutOfBoundsException(
                sprintf('Index \'%d\' is out of range ($index < 0 || $index >= IndexedList::count())', $offset)
            );
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

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->node?->getValue();
    }

    /**
     * @return void
     * @throws InvalidOperationException
     */
    public function next(): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('List was modified');
        }

        $this->node = $this->node?->getNext();
        $this->index++;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        if ($this->head !== null) {
            return $this->index === 0 || $this->node !== $this->head;
        }

        return false;
    }

    /**
     * @return void
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('List was modified');
        }

        $this->node = $this->head;
        $this->index = 0;
    }
}
