<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use OutOfBoundsException;
use SeekableIterator;
use function sprintf;

/**
 * Iterates over the elements of a list.
 */
final class ListCollectionIterator implements SeekableIterator
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
     * @param mixed $offset
     *
     * @return void
     * @throws InvalidOperationException
     * @throws OutOfBoundsException
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

        $this->cursor = $offset;
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
        if ($this->current_version !== $this->list_version) {
            throw new InvalidOperationException('List was modified');
        }

        $this->cursor++;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->cursor;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->cursor < $this->length;
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

        $this->cursor = 0;
    }
}
