<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use Traversable;
use function array_pop;
use function array_reverse;

/**
 * Represents a last-in-first-out (LIFO) collection.
 */
class Stack implements Collection
{
    private array $items = [];
    private int $size = 0;

    /**
     * Stack constructor.
     *
     * @param iterable|null $items A collection of values that will be copied to the stack.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            foreach ($items as $value) {
                $this->push($value);
            }
        }
    }

    /**
     * Returns true if the stack is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all values from the stack.
     */
    public function clear(): void
    {
        $this->items = [];
        $this->size = 0;
    }

    /**
     * Returns true if the stack contains the given value.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($value === $this->items[$i]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the values of the stack to an array.
     * The values are copied in last-in-first-out (LIFO) order.
     *
     * @param array $destination The destination array.
     * @param int $index The index in $destination at which copy begins.
     */
    public function copyTo(array &$destination, int $index = 0): void
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
            $destination[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns a one-dimension array containing all the values in the stack.
     * The values are copied in last-in-first-out (LIFO) order.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_reverse($this->items);
    }

    /**
     * Gets an iterator instance for the stack.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new StackIterator($this->items, $this->size);
    }

    /**
     * Returns the number of values in the stack.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Adds a value at the top of the stack.
     *
     * @param mixed $value The value to push.
     */
    public function push(mixed $value): void
    {
        $this->items[] = $value;
        $this->size++;
    }

    /**
     * Removes and returns the value at the top of the stack.
     *
     * @return mixed
     * @throws InvalidOperationException If the stack is empty.
     */
    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        $this->size--;
        return array_pop($this->items);
    }

    /**
     * Copies and removes the value at the top of the stack to $result.
     *
     * @param mixed $result The value at the top of the stack.
     *
     * @return bool Returns true if the stack is not empty.
     */
    public function tryPop(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $this->size--;
        $result = array_pop($this->items);
        return true;
    }

    /**
     * Returns the value at the top of the stack without removing it.
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
     * Copies the value at the top of the stack to $result.
     *
     * @param mixed $result The value at the top of the stack.
     *
     * @return bool Returns true if the stack is not empty.
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
