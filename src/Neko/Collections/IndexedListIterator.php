<?php declare(strict_types=1);
namespace Neko\Collections;

use OutOfBoundsException;
use SeekableIterator;

/**
 * Iterates through the values of a list.
 */
final class IndexedListIterator implements SeekableIterator
{
    private array $items;
    private int $length;
    private int $cursor = 0;

    public function __construct(array &$items, int $length)
    {
        $this->items = &$items;
        $this->length = $length;
    }

    public function seek(mixed $offset): void
    {
        if ($offset < 0 || $offset >= $this->length) {
            throw new OutOfBoundsException('Offset was out of bounds. Must be non-negative and less than the length of the list');
        }

        $this->cursor = $offset;
    }

    public function current(): mixed
    {
        return $this->items[$this->cursor];
    }

    public function next(): void
    {
        $this->cursor++;
    }

    public function key(): int
    {
        return $this->cursor;
    }

    public function valid(): bool
    {
        return $this->cursor < $this->length;
    }

    public function rewind(): void
    {
        $this->cursor = 0;
    }
}
