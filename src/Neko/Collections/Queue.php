<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use Traversable;
use function array_is_list;
use function array_values;
use function count;
use function is_array;

/**
 * Represents a first-in, first-out collection of elements.
 */
class Queue implements Collection
{
    private array $items = [];
    private int $head = 0;
    private int $length = 0;
    private int $version = 0;

    /**
     * Queue constructor.
     *
     * @param iterable|null $items A collection of values that will be copied to the queue.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            if (is_array($items) && array_is_list($items)) {
                $this->items = $items;
                $this->length = count($items);
            } else {
                foreach ($items as $value) {
                    $this->enqueue($value);
                }
            }
        }
    }

    /**
     * Returns true if the queue contains no elements.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all elements from the queue.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
        $this->head = 0;
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the stack contains a specific element.
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
     * Copies the elements of the queue to an array.
     *
     * @param array $array
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    public function copyTo(array &$array, int $index = 0): void
    {
        foreach ($this->items as $item) {
            $array[$index++] = $item;
        }
    }

    /**
     * Returns an array containing all the elements of the queue.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Returns an iterator over the elements in the queue.
     *
     * @return Traversable
     * @throws InvalidOperationException
     */
    public function getIterator(): Traversable
    {
        $version = $this->version;
        foreach ($this->items as $item) {
            yield $item;

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
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Adds an element to the end of the queue.
     *
     * @param mixed $value The element to add.
     *
     * @return void
     */
    public function enqueue(mixed $value): void
    {
        $this->items[] = $value;
        $this->length++;
        $this->version++;
    }

    /**
     * Retrieves and removes the element at the head of the queue.
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
        $this->length--;
        $this->head++;
        $this->version++;
        return $value;
    }

    /**
     * Removes the element at the head of the queue and copies it to the $result argument.
     *
     * @param mixed $result The value at the head of the queue.
     *
     * @return bool True if the element was removed; false if the queue is empty.
     */
    public function tryDequeue(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->head];
        unset($this->items[$this->head]);
        $this->length--;
        $this->head++;
        $this->version++;
        return true;
    }

    /**
     * Returns the element at the head of the queue without removing it.
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
     * Retrieves the element at the head of the queue and copies it to the $result argument.
     *
     * @param mixed $result The value at the head of the queue.
     *
     * @return bool True if there is an element at the head of the queue; false if the queue is empty.
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
