<?php declare(strict_types=1);
namespace Neko\Collections;

use Iterator;
use Neko\InvalidOperationException;
use Override;
use function array_pop;
use function array_reverse;
use function count;
use function iterator_to_array;

/**
 * Represents a last-in, first-out collection of elements.
 */
class Stack implements Collection
{
    private array $items = [];
    private int $size = 0;
    private int $version = 0;

    /**
     * Stack constructor.
     *
     * @param iterable|null $items A collection of initial elements that will be copied to the stack.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            $this->items = iterator_to_array($items, false);
            $this->size = count($this->items);
        }
    }

    /**
     * Serializes the stack.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->items;
    }

    /**
     * Unserializes the stack.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->items = $data;
        $this->size = count($data);
    }

    /**
     * Returns true if the stack contains no elements.
     *
     * @return bool
     */
    #[Override]
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all elements from the stack.
     *
     * @return void
     */
    #[Override]
    public function clear(): void
    {
        $this->items = [];
        $this->size = 0;
        $this->version++;
    }

    /**
     * Returns true if the stack contains the specified element.
     *
     * @param mixed $item The element to search.
     *
     * @return bool
     */
    #[Override]
    public function contains(mixed $item): bool
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
            if ($item === $this->items[$i]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the stack contains all the elements in the specified collection.
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
     * Copies the elements of the stack to an array, starting at the specified index.
     * The elements are copied in last-in-first-out order.
     *
     * @param array $array REF: The array where the elements of the stack will be copied.
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    #[Override]
    public function copyTo(array &$array, int $index = 0): void
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
            $array[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns an array containing all the elements of the stack.
     * The elements are copied in last-in, first-out order.
     *
     * @return array
     */
    #[Override]
    public function toArray(): array
    {
        return array_reverse($this->items);
    }

    /**
     * Gets an iterator that can traverse through the elements of the stack.
     *
     * @return Iterator
     * @throws InvalidOperationException if the stack was modified within the iterator.
     */
    #[Override]
    public function getIterator(): Iterator
    {
        $version = $this->version;
        for ($i = $this->size - 1; $i >= 0; $i--) {
            yield $this->items[$i];

            if ($version !== $this->version) {
                throw new InvalidOperationException('Stack was modified');
            }
        }
    }

    /**
     * Returns the number of elements in the stack.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Adds the element to the top of the stack.
     *
     * @param mixed $item The element to push.
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
     * Removes and returns the element at the top of the stack.
     *
     * @return mixed
     * @throws InvalidOperationException if the stack is empty.
     */
    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        $this->size--;
        $this->version++;
        return array_pop($this->items);
    }

    /**
     * Tries to remove the element at the top of the stack and copies it to the $result argument.
     *
     * @param mixed $result OUT: The element at the top of the stack.
     *
     * @return bool True if the element was successfully copied and removed; otherwise false.
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
     * Returns the element at the top of the stack without removing it.
     *
     * @return mixed
     * @throws InvalidOperationException if the stack is empty.
     */
    public function peek(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        return $this->items[$this->size - 1];
    }

    /**
     * Tries to get the element at the top of the stack and copies it to the $result argument.
     *
     * @param mixed $result OUT: The element at the top of the stack.
     *
     * @return bool True if the element was successfully copied; otherwise false.
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
}
