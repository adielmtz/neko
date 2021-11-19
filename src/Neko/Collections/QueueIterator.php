<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;

/**
 * Iterates through the values of a queue.
 */
final class QueueIterator implements Iterator
{
    private array $items;
    private int $size;
    private int $head;
    private int $cursor;

    public function __construct(array &$items, int $size, int $head)
    {
        $this->items = &$items;
        $this->size = $size;
        $this->head = $head;
        $this->cursor = $head;
    }

    public function current(): mixed
    {
        return $this->items[$this->cursor];
    }

    public function next(): void
    {
        $this->cursor++;
    }

    /**
     * @throws InvalidOperationException
     */
    public function key(): void
    {
        throw new InvalidOperationException('Accessing the key is not valid for a queue collection');
    }

    public function valid(): bool
    {
        return $this->cursor < $this->head + $this->size;
    }

    public function rewind(): void
    {
        $this->cursor = $this->head;
    }
}
