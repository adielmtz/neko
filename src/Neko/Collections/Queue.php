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
     * @param iterable|null $items A collection of initial elements that will be copied to the queue.
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
        $this->length = count($data);
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
     * Returns true if the queue contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
    public function contains(mixed $item): bool
    {
        $tail = $this->head + $this->length;
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
    public function containsAll(iterable $items): bool
    {
        foreach ($items as $value) {
            if (!$this->contains($value)) {
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
    public function copyTo(array &$array, int $index = 0): void
    {
        $tail = $this->head + $this->length;
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
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Gets an iterator that can traverse through the elements of the queue.
     *
     * @return Traversable
     * @throws InvalidOperationException if the queue was modified within the iterator.
     */
    public function getIterator(): Traversable
    {
        $version = $this->version;
        $tail = $this->head + $this->length;
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
    public function count(): int
    {
        return $this->length;
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
        $this->length++;
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
        $this->length--;
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
        $this->length--;
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
