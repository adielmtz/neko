<?php declare(strict_types=1);
namespace Neko\Collections;

use InvalidArgumentException;

/**
 * Defines a collection of key/value pairs.
 */
interface KeyValuePairCollection extends Collection
{
    /**
     * Returns true if the collection contains a specific key.
     *
     * @param mixed $key The key to search.
     *
     * @return bool
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function containsKey(mixed $key): bool;

    /**
     * Returns true if the collection contains a specific value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function containsValue(mixed $value): bool;

    /**
     * Returns an array containing all the keys of the collection.
     *
     * @return array
     */
    public function getKeys(): array;

    /**
     * Returns an array containing all the values of the collection.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Adds a key and a value pair to the collection.
     *
     * @param mixed $key The key that maps to the value.
     * @param mixed $value The value of the element to add.
     *
     * @return void
     * @throws InvalidArgumentException if the key is of type array or a resource or the key already exists in the
     *     collection.
     */
    public function add(mixed $key, mixed $value): void;

    /**
     * Returns the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value to return.
     *
     * @return mixed
     * @throws InvalidArgumentException if the key is of type array or a resource.
     * @throws KeyNotFoundException if the key does not exist in the dictionary.
     */
    public function get(mixed $key): mixed;

    /**
     * Replaces the value associated to the specified key or sets a new key and value pair to the collection.
     *
     * @param mixed $key The key of the value to set or replace.
     * @param mixed $value The value of the element to set or replace.
     *
     * @return void
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * Removes the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function remove(mixed $key): bool;
}
