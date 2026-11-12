<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use InvalidArgumentException;

/**
 * Represents a collection of key/value pairs that can be accessed by key.
 */
interface Map extends ArrayAccess, Collection
{
    /**
     * Determines whether the map contains the specified key.
     *
     * @param mixed $key The key to search for.
     *
     * @return bool True if the map contains the key; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    public function containsKey(mixed $key): bool;

    /**
     * Determines whether the map contains one or more keys associated with the specified value.
     *
     * @param mixed $value The value to search for.
     *
     * @return bool True if the map contains the value; otherwise, false.
     */
    public function containsValue(mixed $value): bool;

    /**
     * Returns an array containing all keys in the map.
     *
     * @return array
     */
    public function getKeys(): array;

    /**
     * Returns an array containing all values in the map.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Returns the value associated with the specified key.
     *
     * @param mixed $key The key of the value to retrieve.
     *
     * @return mixed
     * @throws InvalidArgumentException If the type of the key is not valid.
     * @throws KeyNotFoundException If the key does not exist in the map.
     */
    public function get(mixed $key): mixed;

    /**
     * Associates the specified value with the specified key.
     * If the key already exists in the map, its value is replaced.
     *
     * @param mixed $key The key of the value to set.
     * @param mixed $value The value to associate with the key.
     *
     * @return void
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * Removes the specified key and its associated value from the map, if present.
     *
     * @param mixed $key The key to remove.
     *
     * @return bool True if the key was removed; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    public function removeKey(mixed $key): bool;
}
