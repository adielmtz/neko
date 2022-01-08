<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;

/**
 * Iterates through the values of a stack in last-in-first-out order.
 */
final class StackIterator implements Iterator
{
    private array $items;
    private int $length;
    private int $cursor;

    private int $stack_version;
    private int $current_version;

    public function __construct(array &$items, int $length, int &$version)
    {
        $this->items = &$items;
        $this->length = $length;
        $this->cursor = $length - 1;
        $this->stack_version = &$version;
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
        if ($this->current_version !== $this->stack_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        $this->cursor--;
    }

    /**
     * @throws InvalidOperationException
     */
    public function key(): void
    {
        throw new InvalidOperationException('Accessing the key is not valid for a stack collection');
    }

    public function valid(): bool
    {
        return $this->cursor >= 0;
    }

    /**
     * @throws InvalidOperationException
     */
    public function rewind(): void
    {
        if ($this->current_version !== $this->stack_version) {
            throw new InvalidOperationException('Collection was modified');
        }

        $this->cursor = $this->length - 1;
    }
}
