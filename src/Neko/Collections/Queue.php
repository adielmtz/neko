<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;
use Override;
use function array_values;
use function count;
use function iterator_to_array;

/**
 * Represents a first-in, first-out collection of elements.
 */
class Queue implements Collection
{
    private array $items = [];
    private int $head = 0;
    private int $size = 0;
    private int $version = 0;

    /**
     * Queue constructor.
     *
     * @param iterable|null $items A collection of initial elements that will be copied to the queue.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, false);
            $this->size = count($this->items);
        }
    }

    /**
     * Serializes the queue.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserializes the queue.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->items = $data;
        $this->head = 0;
        $this->size = count($data);
    }

    /**
     * Returns true if the queue contains no elements.
     *
     * @return bool
     */
    #[Override]
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all elements from the queue.
     *
     * @return void
     */
    #[Override]
    public function clear(): void
    {
        $this->items = [];
        $this->head = 0;
        $this->size = 0;
        $this->version++;
    }

    /**
     * Returns true if the queue contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
    #[Override]
    public function contains(mixed $item): bool
    {
        $tail = $this->head + $this->size;
        for ($i = $this->head; $i < $tail; $i++) {
            if ($item === $this->items[$i]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the queue contains all the elements in the specified collection.
     *
     * @param iterable $items The collection to search.
     *
     * @return bool
     */
    #[Override]
    public function containsAll(iterable $items): bool
    {
        foreach ($items as $item) {
            if (!$this->contains($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copies the elements of the queue to an array, starting at the specified index.
     * The elements are copied in first-in, first-out order.
     *
     * @param array $array REF: The array where the elements of the queue will be copied.
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    #[Override]
    public function copyTo(array &$array, int $index = 0): void
    {
        $tail = $this->head + $this->size;
        for ($i = $this->head; $i < $tail; $i++) {
            $array[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns an array containing all the elements of the queue.
     * The elements are copied in first-in, first-out order.
     *
     * @return array
     */
    #[Override]
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Gets an iterator that can traverse through the elements of the queue.
     *
     * @return Iterator
     * @throws InvalidOperationException if the queue was modified within the iterator.
     */
    #[Override]
    public function getIterator(): Iterator
    {
        $version = $this->version;
        $tail = $this->head + $this->size;
        for ($i = $this->head; $i < $tail; $i++) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new InvalidOperationException('Queue was modified');
            }
        }
    }

    /**
     * Returns the number of elements in the queue.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Adds the element to the end of the queue.
     *
     * @param mixed $item The element to add.
     *
     * @return void
     */
    public function enqueue(mixed $item): void
    {
        $this->items[] = $item;
        $this->size++;
        $this->version++;
    }

    /**
     * Removes and returns the element at the beginning of the queue.
     *
     * @return mixed
     * @throws InvalidOperationException if the queue is empty.
     */
    public function dequeue(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Queue is empty');
        }

        $value = $this->items[$this->head];
        unset($this->items[$this->head]);
        $this->size--;
        $this->head++;
        $this->version++;
        return $value;
    }

    /**
     * Tries to remove the element at the beginning of the queue and copies it to the $result argument.
     *
     * @param mixed $result OUT: The element at the beginning of the queue.
     *
     * @return bool True if the element was successfully copied and removed; otherwise false.
     */
    public function tryDequeue(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->head];
        unset($this->items[$this->head]);
        $this->size--;
        $this->head++;
        $this->version++;
        return true;
    }

    /**
     * Returns the element at the beginning of the queue without removing it.
     *
     * @return mixed
     * @throws InvalidOperationException if the queue is empty.
     */
    public function peek(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Queue is empty');
        }

        return $this->items[$this->head];
    }

    /**
     * Tries to get the element at the beginning of the queue without removing it and copies it to the $result argument.
     *
     * @param mixed $result OUT: The element at the beginning of the queue.
     *
     * @return bool True if the element was successfully copied; otherwise false.
     */
    public function tryPeek(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->head];
        return true;
    }
}
