<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use Traversable;
use function array_values;

/**
 * Represents a first-in-first-out (FIFO) collection.
 */
class Queue implements Collection
{
    private array $items = [];
    private int $head = 0;
    private int $size = 0;

    /**
     * Queue constructor.
     *
     * @param iterable|null $items A collection of values that will be copied to the queue.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            foreach ($items as $value) {
                $this->enqueue($value);
            }
        }
    }

    /**
     * Returns true if the queue is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all values from the queue.
     */
    public function clear(): void
    {
        $this->items = [];
        $this->head = 0;
        $this->size = 0;
    }

    /**
     * Returns true if the queue contains the given value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        foreach ($this->items as $item) {
            if ($value === $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the values of the queue to an array.
     *
     * @param array $destination The destination array.
     * @param int $index The index in $destination at which copy begins.
     */
    public function copyTo(array &$destination, int $index = 0): void
    {
        foreach ($this->items as $item) {
            $destination[$index++] = $item;
        }
    }

    /**
     * Returns a one-dimension array containing all the values in the queue.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Gets an iterator instance for the queue.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new QueueIterator($this->items, $this->size, $this->head);
    }

    /**
     * Returns the number of values in the queue.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Adds a value at the end of the queue.
     *
     * @param mixed $value The value to add.
     */
    public function enqueue(mixed $value): void
    {
        $this->items[] = $value;
        $this->size++;
    }

    /**
     * Removes and returns the value at the start of the queue.
     *
     * @return mixed
     * @throws InvalidOperationException If the queue is empty.
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
        return $value;
    }

    /**
     * Copies and removes the value at the start of the queue to $result.
     *
     * @param mixed $result The value at the start of the queue.
     *
     * @return bool Returns true if the queue is not empty.
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
        return true;
    }

    /**
     * Returns the value at the start of the queue without removing it.
     *
     * @return mixed
     * @throws InvalidOperationException If the queue is empty.
     */
    public function peek(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Queue is empty');
        }

        return $this->items[$this->head];
    }

    /**
     * Copies the value at the start of the queue to $result.
     *
     * @param mixed $result The value at the start of the queue.
     *
     * @return bool Returns true if the queue is not empty.
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
