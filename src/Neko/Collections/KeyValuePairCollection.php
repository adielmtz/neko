<?php declare(strict_types=1);
namespace Neko\Collections;

use InvalidArgumentException;

interface KeyValuePairCollection extends Collection
{
    /**
     * Gets an array with the collection keys.
     *
     * @return array
     */
    public function getKeys(): array;

    /**
     * Gets an array with the collection values.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Adds a key and value to the collection.
     * Throws an exception if a value with the specified key already exists.
     *
     * @param mixed $key The key to add.
     * @param mixed $value The value add.
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $key, mixed $value): void;

    /**
     * Gets the value associated to the specified key.
     *
     * @param mixed $key The key of the value to get.
     *
     * @return mixed
     */
    public function get(mixed $key): mixed;

    /**
     * Sets the value associated to the specified key.
     *
     * @param mixed $key The key of the value to set.
     * @param mixed $value The value associated to the specified key.
     *
     * @return void
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * Removes the value associated to the specified key in the collection.
     *
     * @param mixed $key The key of the value to remove.
     *
     * @return bool True if the value was removed, false otherwise (key not found).
     */
    public function remove(mixed $key): bool;

    /**
     * Returns true if the collection contains the specified key.
     *
     * @param mixed $key The key to search.
     *
     * @return bool
     */
    public function containsKey(mixed $key): bool;

    /**
     * Returns true if the collection contains the specified value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function containsValue(mixed $value): bool;
}
