<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;
use Neko\InvalidOperationException;
use Override;
use Traversable;
use function array_values;
use function count;
use function in_array;
use function iterator_to_array;

/**
 * Represents a first-in-first-out (FIFO) queue.
 */
final class Queue implements Countable, IteratorAggregate
{
    private array $items = [];
    private int $size = 0;
    private int $head = 0;
    private int $version = 0;

    /**
     * Queue constructor.
     *
     * @param iterable|null $items If not null, the items to initialize the queue with.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, preserve_keys: false);
            $this->size = count($this->items);
        }
    }

    /**
     * Determines whether the queue is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Determines whether the queue contains a specific item.
     *
     * @param mixed $item The item to search for.
     *
     * @return bool
     */
    public function contains(mixed $item): bool
    {
        return in_array($item, $this->items, strict: true);
    }

    /**
     * Returns the number of items in the queue.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Removes all items from the queue.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
        $this->size = 0;
        $this->head = 0;
        $this->version++;
    }

    /**
     * Returns an iterator over the items in the queue, in first-in-first-out order.
     *
     * @return Traversable
     * @throws ConcurrentMutationException If the queue is modified during iteration.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $version = $this->version;
        $tail = $this->head + $this->size;
        for ($i = $this->head; $i < $tail; ++$i) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new ConcurrentMutationException('Queue was modified');
            }
        }
    }

    /**
     * Adds an item to the end of the queue.
     *
     * @param mixed $item The item to add.
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
     * Removes and returns the item at the beginning of the queue.
     *
     * @return mixed
     * @throws InvalidOperationException If the queue is empty.
     */
    public function dequeue(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Queue is empty');
        }

        $item = $this->items[$this->head];
        $this->removeHead();
        return $item;
    }

    /**
     * Returns the item at the beginning of the queue without removing it.
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
     * Determines whether the queue is non-empty and, if so, removes the first item and assigns it to $result.
     *
     * @param mixed $result OUT: The first item, or null if the queue is empty.
     *
     * @return bool True if an item was removed; otherwise, false.
     */
    public function tryDequeue(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->head];
        $this->removeHead();
        return true;
    }

    /**
     * Determines whether the queue is non-empty and, if so, assigns the first item to $result.
     *
     * @param mixed $result OUT: The first item, or null if the queue is empty.
     *
     * @return bool True if the queue is non-empty; otherwise, false.
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

    /**
     * Returns an array containing all the items of the queue.
     * The items are copied in first-in-first-out order.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_values($this->items);
    }

    /**
     * Removes the item at the beginning of the queue. The queue must not be empty.
     *
     * @return void
     */
    private function removeHead(): void
    {
        unset($this->items[$this->head]);
        $this->size--;
        $this->version++;

        if ($this->size === 0) {
            // Start over from index 0. PHP does not serialize the next free index of an array,
            // so an empty queue that kept the old head would enqueue at the wrong index once restored.
            $this->items = [];
            $this->head = 0;
        } else {
            $this->head++;
        }
    }
}
