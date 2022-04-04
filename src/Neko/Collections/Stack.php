<?php declare(strict_types=1);
namespace Neko\Collections;

use Neko\InvalidOperationException;
use Traversable;
use function array_pop;
use function array_reverse;

/**
 * Represents a last-in, first-out collection of elements.
 */
class Stack implements Collection
{
    private array $items = [];
    private int $length = 0;
    private int $version = 0;

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
     * Returns true if the stack contains no elements.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all elements from the stack.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
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
        for ($i = 0; $i < $this->length; $i++) {
            if ($value === $this->items[$i]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the elements of the stack to an array.
     * The elements of the stack are copied in a last-in, first-out order.
     *
     * @param array $array
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    public function copyTo(array &$array, int $index = 0): void
    {
        for ($i = $this->length - 1; $i >= 0; $i--) {
            $array[$index++] = $this->items[$i];
        }
    }

    /**
     * Returns an array containing all the elements of the stack.
     * The elements of the stack are copied in a last-in, first-out order.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_reverse($this->items);
    }

    /**
     * Returns an iterator over the elements in the stack.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new StackIterator($this->items, $this->length, $this->version);
    }

    /**
     * Returns the number of elements in the stack.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Adds an element to the top of the stack.
     *
     * @param mixed $value The element to push.
     *
     * @return void
     */
    public function push(mixed $value): void
    {
        $this->items[] = $value;
        $this->length++;
        $this->version++;
    }

    /**
     * Retrieves and removes the element at the top of the stack.
     *
     * @return mixed
     * @throws InvalidOperationException if the stack is empty.
     */
    public function pop(): mixed
    {
        if ($this->isEmpty()) {
            throw new InvalidOperationException('Stack is empty');
        }

        $this->length--;
        $this->version++;
        return array_pop($this->items);
    }

    /**
     * Removes the element at the top of the stack and copies it to the $result argument.
     *
     * @param mixed $result The value at the top of the stack.
     *
     * @return bool True if the element was removed; false if the stack is empty.
     */
    public function tryPop(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $this->length--;
        $this->version++;
        $result = array_pop($this->items);
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

        return $this->items[$this->length - 1];
    }

    /**
     * Retrieves the element at the top of the stack and copies it to the $result argument.
     *
     * @param mixed $result The value at the top of the stack.
     *
     * @return bool True if there is an element at the top of the stack; false if the stack is empty.
     */
    public function tryPeek(mixed &$result): bool
    {
        if ($this->isEmpty()) {
            $result = null;
            return false;
        }

        $result = $this->items[$this->length - 1];
        return true;
    }
}
