<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;

/**
 * Iterates over the elements of a queue collection.
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

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->items[$this->cursor];
    }

    /**
     * @return void
     * @throws InvalidOperationException
     */
    public function next(): void
    {
        if ($this->current_version !== $this->queue_version) {
            throw new InvalidOperationException('Queue was modified');
        }

        $this->cursor++;
    }

    /**
     * @return mixed
     * @throws InvalidOperationException
     */
    public function key(): mixed
    {
        throw new InvalidOperationException('Accessing the key is not valid for a queue collection');
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->cursor < $this->head + $this->length;
    }

    /**
     * @return void
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->queue_version) {
            throw new InvalidOperationException('Queue was modified');
        }

        $this->cursor = $this->head;
    }
}
