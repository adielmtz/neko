<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;

/**
 * Iterates through the values of a queue in first-in-first-out order.
 */
final class QueueIterator implements Iterator
{
    private array $items;
    private int $length;
    private int $head;
    private int $cursor;

    private int $queue_version;
    private int $current_version;

    public function __construct(array &$items, int $length, int $head, int &$version)
    {
        $this->items = &$items;
        $this->length = $length;
        $this->head = $head;
        $this->cursor = $head;
        $this->queue_version = &$version;
        $this->current_version = $version;
    }

    public function current(): mixed
    {
        return $this->items[$this->cursor];
    }

    /**
     * @throws InvalidOperationException
     */
    public function next(): void
    {
        if ($this->current_version !== $this->queue_version) {
            throw new InvalidOperationException('Collection was modified');
        }

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
        return $this->cursor < $this->head + $this->length;
    }

    /**
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->queue_version) {
            throw new InvalidOperationException('Collection was modified');
        }
        $this->cursor = $this->head;
    }
}
