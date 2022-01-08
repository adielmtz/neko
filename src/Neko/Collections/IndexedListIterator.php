<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
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

    private int $list_version;
    private int $current_version;

    public function __construct(array &$items, int $length, int &$version)
    {
        $this->items = &$items;
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
            throw new OutOfBoundsException('Offset was out of bounds. Must be non-negative and less than the length of the list');
        }

        $this->cursor = $offset;
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
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('Collection was modified');
        }

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

    /**
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        $this->cursor = 0;
    }
}
