<?php declare(strict_types=1);
namespace Neko\Collections;

use InvalidArgumentException;
use Neko\NotSupportedException;
use Override;
use Traversable;
use TypeError;
use function gettype;
use function is_object;
use function is_string;
use function spl_object_id;
use function sprintf;
use function strlen;
use function substr;
use function var_export;

/**
 * Represents a collection of key/value pairs that can be accessed by key.
 * Entries are kept in the order in which their keys were first added.
 */
final class Dictionary implements Map
{
    /**
     * The maximum number of bytes of a string key that are included in exception messages.
     */
    private const int MAX_KEY_LENGTH_IN_MESSAGES = 64;

    /**
     * @var array<int|string, KeyValuePair> The entries, indexed by the hash of their keys.
     */
    private array $items = [];
    private int $size = 0;
    private int $version = 0;

    /**
     * Dictionary constructor.
     *
     * @param iterable|null $items If not null, the key/value pairs to initialize the dictionary with.
     *
     * @throws InvalidArgumentException If the type of any key is not valid.
     */
    public function __construct(?iterable $items = null)
    {
        if ($items !== null) {
            foreach ($items as $key => $value) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Handles clone operator.
     *
     * @return void
     */
    public function __clone(): void
    {
        // Entries are mutable, so they must not be shared with the original dictionary
        foreach ($this->items as $hash => $entry) {
            $this->items[$hash] = new KeyValuePair($entry->key, $entry->value);
        }

        $this->version = 0;
    }

    /**
     * Returns the data to serialize.
     *
     * @return array
     */
    public function __serialize(): array
    {
        // Object hashes are not stable across requests, so only keys and values are stored
        $data = [];
        foreach ($this->items as $entry) {
            $data[] = [$entry->key, $entry->value];
        }

        return $data;
    }

    /**
     * Restores the dictionary from the serialized data.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->items = [];
        $this->size = 0;
        $this->version = 0;

        foreach ($data as [$key, $value]) {
            $this->set($key, $value);
        }
    }

    /**
     * Determines whether the dictionary is empty.
     *
     * @return bool True if the dictionary is empty; otherwise, false.
     */
    #[Override]
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Determines whether the dictionary contains a specific key/value pair.
     * The pair is found if its key exists in the dictionary and is associated with an identical value.
     *
     * @param mixed $item The key/value pair to search for.
     *
     * @return bool True if the dictionary contains the key/value pair; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function contains(mixed $item): bool
    {
        return $this->findEntryHash($item) !== null;
    }

    /**
     * Determines whether the dictionary contains the specified key.
     *
     * @param mixed $key The key to search for.
     *
     * @return bool True if the dictionary contains the key; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function containsKey(mixed $key): bool
    {
        return isset($this->items[self::hashKey($key)]);
    }

    /**
     * Determines whether the dictionary contains one or more keys associated with the specified value.
     *
     * @param mixed $value The value to search for.
     *
     * @return bool True if the dictionary contains the value; otherwise, false.
     */
    #[Override]
    public function containsValue(mixed $value): bool
    {
        foreach ($this->items as $entry) {
            if ($value === $entry->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the number of key/value pairs in the dictionary.
     *
     * @return int
     */
    #[Override]
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Removes all key/value pairs from the dictionary.
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
     * Returns an iterator over the key/value pairs in the dictionary.
     * Each entry is yielded with its key as the iteration key.
     *
     * @return Traversable
     * @throws ConcurrentMutationException If the dictionary is modified during iteration.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $version = $this->version;
        foreach ($this->items as $entry) {
            yield $entry->key => $entry->value;

            if ($version !== $this->version) {
                throw new ConcurrentMutationException('Dictionary was modified');
            }
        }
    }

    /**
     * Returns an array containing all keys in the dictionary.
     *
     * @return array
     */
    #[Override]
    public function getKeys(): array
    {
        $keys = [];
        foreach ($this->items as $entry) {
            $keys[] = $entry->key;
        }

        return $keys;
    }

    /**
     * Returns an array containing all values in the dictionary.
     *
     * @return array
     */
    #[Override]
    public function getValues(): array
    {
        $values = [];
        foreach ($this->items as $entry) {
            $values[] = $entry->value;
        }

        return $values;
    }

    /**
     * Returns the value associated with the specified key.
     *
     * @param mixed $key The key of the value to retrieve.
     *
     * @return mixed
     * @throws InvalidArgumentException If the type of the key is not valid.
     * @throws KeyNotFoundException If the key does not exist in the dictionary.
     */
    #[Override]
    public function get(mixed $key): mixed
    {
        $hash = self::hashKey($key);
        if (!isset($this->items[$hash])) {
            throw new KeyNotFoundException('Key ' . self::describeKey($key) . ' does not exist in the dictionary');
        }

        return $this->items[$hash]->value;
    }

    /**
     * Determines whether the dictionary contains the specified key and, if so, assigns its value to $value.
     *
     * @param mixed $key The key of the value to retrieve.
     * @param mixed $value OUT: The value associated with the key, or null if the key does not exist.
     *
     * @return bool True if the dictionary contains the key; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function tryGet(mixed $key, mixed &$value): bool
    {
        $entry = $this->items[self::hashKey($key)] ?? null;
        if ($entry === null) {
            $value = null;
            return false;
        }

        $value = $entry->value;
        return true;
    }

    /**
     * Associates the specified value with the specified key.
     * If the key already exists in the dictionary, its value is replaced.
     *
     * @param mixed $key The key of the value to set.
     * @param mixed $value The value to associate with the key.
     *
     * @return void
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function set(mixed $key, mixed $value): void
    {
        $hash = self::hashKey($key);
        if (isset($this->items[$hash])) {
            $this->items[$hash]->value = $value;
        } else {
            $this->items[$hash] = new KeyValuePair($key, $value);
            $this->size++;
        }

        $this->version++;
    }

    /**
     * Adds a key/value pair to the dictionary.
     *
     * @param mixed $item The key/value pair to add.
     *
     * @return void
     * @throws TypeError If the item is not a KeyValuePair.
     * @throws InvalidArgumentException If the type of the key is not valid or the key already exists in the dictionary.
     */
    #[Override]
    public function add(mixed $item): void
    {
        if (!$item instanceof KeyValuePair) {
            throw new TypeError('Argument #1 ($item) must be of type ' . KeyValuePair::class);
        }

        $hash = self::hashKey($item->key);
        if (isset($this->items[$hash])) {
            throw new InvalidArgumentException('Key ' . self::describeKey($item->key) . ' already exists in the dictionary');
        }

        // Store a copy, so that changes made to the given pair do not affect the dictionary
        $this->items[$hash] = new KeyValuePair($item->key, $item->value);
        $this->size++;
        $this->version++;
    }

    /**
     * Removes the specified key/value pair from the dictionary, if present.
     * The pair is removed only if its key exists in the dictionary and is associated with an identical value.
     *
     * @param mixed $item The key/value pair to remove.
     *
     * @return bool True if the key/value pair was removed; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function remove(mixed $item): bool
    {
        $hash = $this->findEntryHash($item);
        if ($hash === null) {
            return false;
        }

        $this->removeEntry($hash);
        return true;
    }

    /**
     * Removes the specified key and its associated value from the dictionary, if present.
     *
     * @param mixed $key The key to remove.
     *
     * @return bool True if the key was removed; otherwise, false.
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function removeKey(mixed $key): bool
    {
        $hash = self::hashKey($key);
        if (!isset($this->items[$hash])) {
            return false;
        }

        $this->removeEntry($hash);
        return true;
    }

    /**
     * Returns an array containing all key/value pairs in the dictionary.
     *
     * @return KeyValuePair[]
     */
    #[Override]
    public function toArray(): array
    {
        $items = [];
        foreach ($this->items as $entry) {
            $items[] = new KeyValuePair($entry->key, $entry->value);
        }

        return $items;
    }

    /**
     * Returns the hash of the stored entry that matches the specified key/value pair,
     * or null if no matching entry is found.
     *
     * @param mixed $item The key/value pair to search for.
     *
     * @return int|string|null
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    private function findEntryHash(mixed $item): int|string|null
    {
        if (!$item instanceof KeyValuePair) {
            return null;
        }

        $hash = self::hashKey($item->key);
        $entry = $this->items[$hash] ?? null;
        if ($entry === null || $entry->value !== $item->value) {
            return null;
        }

        return $hash;
    }

    /**
     * Removes the entry with the specified hash. The entry must exist.
     *
     * @param int|string $hash The hash of the key to remove.
     *
     * @return void
     */
    private function removeEntry(int|string $hash): void
    {
        unset($this->items[$hash]);
        $this->size--;
        $this->version++;
    }

    /**
     * Returns a human-readable representation of a key, used in exception messages.
     *
     * @param mixed $key The key to describe.
     *
     * @return string
     */
    private static function describeKey(mixed $key): string
    {
        if (is_object($key)) {
            return 'of type ' . $key::class;
        }

        // Keep messages short when the key is a long string
        if (is_string($key) && strlen($key) > self::MAX_KEY_LENGTH_IN_MESSAGES) {
            return var_export(substr($key, 0, self::MAX_KEY_LENGTH_IN_MESSAGES), true) . '...';
        }

        return var_export($key, true);
    }

    /**
     * Returns the hash of a key, used to index the entries of the dictionary.
     *
     * The hash is a lossless encoding of the key, so two keys share a hash only if they are identical.
     * Integers are used as is; every other type is encoded as a string with a non-numeric prefix,
     * so it can never be cast to an integer array key or collide with a key of another type.
     *
     * @param mixed $key The key to hash.
     *
     * @return int|string
     * @throws InvalidArgumentException If the type of the key is not valid, like an array or a resource.
     */
    private static function hashKey(mixed $key): int|string
    {
        $type = gettype($key);
        return match ($type) {
            'integer' => $key,
            'string'  => 's:' . $key,
            // The dictionary holds a reference to the key, so its id cannot be reused while it is stored
            'object'  => 'o:' . spl_object_id($key),
            // -0.0 === 0.0, so both must share a hash; %h is the locale-independent version of %g.
            // NAN !== NAN, but every NAN still maps to the same key, so a NAN key can be found again.
            'double'  => 'd:' . sprintf('%.17h', $key === 0.0 ? 0.0 : $key),
            'boolean' => $key ? 'b:1' : 'b:0',
            'NULL'    => 'n:',
            default   => throw new InvalidArgumentException("Value of type '$type' is not a valid key"),
        };
    }

    #region Array Access implementation
    /**
     * Determines whether the specified key exists and is associated with a non-null value,
     * matching the behavior of isset() on arrays. Use containsKey() to check for the key alone.
     *
     * @param mixed $offset The key to search for.
     *
     * @return bool
     * @throws InvalidArgumentException If the type of the key is not valid.
     */
    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[self::hashKey($offset)]->value);
    }

    /**
     * Returns the value associated with the specified key.
     *
     * The value is returned by value, so modifying a stored array in place (e.g. $dictionary['tags'][] = $x)
     * has no effect. Read the value, modify it, and assign it back instead.
     *
     * @param mixed $offset The key of the value to retrieve.
     *
     * @return mixed
     * @throws InvalidArgumentException If the type of the key is not valid.
     * @throws KeyNotFoundException If the key does not exist in the dictionary.
     */
    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Associates the specified value with the specified key.
     *
     * @param mixed $offset The key of the value to set.
     * @param mixed $value The value to associate with the key.
     *
     * @return void
     * @throws InvalidArgumentException If the type of the key is not valid.
     * @throws NotSupportedException If the key is null, as in $dictionary[] = $value.
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // $dictionary[] = $value and $dictionary[null] = $value are indistinguishable here
        if ($offset === null) {
            throw new NotSupportedException('Appending to a dictionary is not supported; use set() to associate a value with a null key');
        }

        $this->set($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->removeKey($offset);
    }
    #endregion
}
