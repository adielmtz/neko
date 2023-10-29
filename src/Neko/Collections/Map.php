<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Defines a collection of key/value pairs.
 */
interface Map extends Collection
{
    /**
     * Returns true if the map contains the specified key.
     *
     * @param mixed $key The key to lookup.
     *
     * @return bool
     */
    public function containsKey(mixed $key): bool;

    /**
     * Returns true if the map contains the specified value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function containsValue(mixed $value): bool;

    /**
     * Returns an array containing the keys of the map.
     *
     * @return array
     */
    public function getKeys(): array;

    /**
     * Returns an array containing the values of the map.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Adds a value associated to the specified key.
     *
     * @param mixed $key The key associated with the value.
     * @param mixed $value The value to add.
     *
     * @return void
     */
    public function add(mixed $key, mixed $value): void;

    /**
     * Gets the value associated to the specified key.
     *
     * @param mixed $key The key to lookup.
     *
     * @return mixed
     * @throws KeyNotFoundException if the map does not contain the specified key.
     */
    public function get(mixed $key): mixed;

    /**
     * Sets or replaces the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value.
     * @param mixed $value The value to set.
     *
     * @return void
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * Removes the value associated with the specified key.
     *
     * @param mixed $key The key associated with the value to remove.
     *
     * @return bool True if the value was successfully removed; otherwise false.
     */
    public function remove(mixed $key): bool;
}
