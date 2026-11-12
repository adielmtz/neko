<?php declare(strict_types=1);
namespace Neko\Collections;

use Countable;
use IteratorAggregate;
use Neko\InvalidOperationException;
use Override;
use Traversable;
use function array_pop;
use function array_reverse;
use function count;
use function in_array;
use function iterator_to_array;

/**
 * Represents a last-in-first-out (LIFO) collection.
 */
final class Stack implements Countable, IteratorAggregate
{
    private array $items = [];
    private int $size = 0;
    private int $version = 0;

    /**
     * Stack constructor.
     *
     * @param iterable|null $items If not null, the items to initialize the stack with.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, preserve_keys: false);
            $this->size = count($this->items);
        }
    }

    /**
     * Determines whether the stack is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Determines whether the stack contains a specific item.
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
     * Returns the number of items in the stack.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Removes all items from the stack.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
        $this->size = 0;
        $this->version++;
    }

    /**
     * Returns an iterator over the items in the stack, in last-in-first-out order.
     *
     * @return Traversable
     * @throws ConcurrentMutationException If the stack is modified during iteration.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $version = $this->version;
        for ($i = $this->size - 1; $i >= 0; --$i) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new ConcurrentMutationException('Stack was modified');
            }
        }
    }

    /**
     * Pushes an item onto the top of the stack.
     *
     * @param mixed $item The item to push.
     *
     * @return void
     */
    public function push(mixed $item): void
    {
        $this->items[] = $item;
        $this->size++;
        $this->version++;
    }

    /**
     * Removes and returns the item at the top of the stack.
     *
     * @return mixed
     * @throws InvalidOperationException If the stack is empty.
     */
    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        $item = array_pop($this->items);
        $this->size--;
        $this->version++;
        return $item;
    }

    /**
     * Returns the item at the top of the stack without removing it.
     *
     * @return mixed
     * @throws InvalidOperationException If the stack is empty.
     */
    public function peek(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        return $this->items[$this->size - 1];
    }

    /**
     * Determines whether the stack is non-empty and, if so, removes the top item and assigns it to $result.
     *
     * @param mixed $result OUT: The top item, or null if the stack is empty.
     *
     * @return bool True if an item was removed; otherwise, false.
     */
    public function tryPop(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = array_pop($this->items);
        $this->size--;
        $this->version++;
        return true;
    }

    /**
     * Determines whether the stack is non-empty and, if so, assigns the top item to $result.
     *
     * @param mixed $result OUT: The top item, or null if the stack is empty.
     *
     * @return bool True if the stack is non-empty; otherwise, false.
     */
    public function tryPeek(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->size - 1];
        return true;
    }

    /**
     * Returns an array containing all the items of the stack.
     * The items are copied in last-in-first-out order.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_reverse($this->items);
    }
}
