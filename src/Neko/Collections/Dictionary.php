<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;
use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use function array_key_exists;
use function count;
use function function_exists;
use function gettype;
use function is_object;
use function spl_object_hash;
use function sprintf;

/**
 * Represents a collection of key/value pairs.
 */
class Dictionary implements ArrayAccess, Map
{
    /**
     * @var KeyValuePair[]
     */
    private array $entries = [];
    private int $size = 0;
    private int $version = 0;

    /**
     * Dictionary constructor.
     *
     * @param iterable|null $items A collection of initial elements that will be copied to the dictionary.
     *
     * @throws NotSupportedException if spl_object_hash is not available.
     */
    public function __construct(?iterable $items = null)
    {
        if (!function_exists('spl_object_hash')) {
            throw new NotSupportedException(
                'Dictionary class requires spl_object_hash() function which is not available for this PHP installation',
            );
        }

        if ($items !== null) {
            foreach ($items as $key => $value) {
                $this->add($key, $value);
            }
        }
    }

    /**
     * Serializes the dictionary.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserializes the dictionary.
     *
     * @param array $data The data provided by unserialize().
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $entry) {
            $arrayKey = self::createValidArrayKey($entry->key);
            $this->entries[$arrayKey] = $entry;
        }

        $this->size = count($this->entries);
    }

    /**
     * Returns true if the dictionary contains no entries.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Removes all entries from the dictionary.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->entries = [];
        $this->size = 0;
        $this->version++;
    }

    /**
     * Returns true if the dictionary contains the specified key/value pair.
     *
     * @param mixed $item The key/value pair to search.
     *
     * @return bool
     */
    public function contains(mixed $item): bool
    {
        if ($item instanceof KeyValuePair) {
            $arrayKey = self::createValidArrayKey($item->key);
            if (array_key_exists($arrayKey, $this->entries)) {
                $entry = $this->entries[$arrayKey];
                return $entry === $item || $entry->value === $item->value;
            }
        }

        return false;
    }

    /**
     * Returns true if the dictionary contains all the elements in the specified collection.
     *
     * @param iterable $items The collection to search.
     *
     * @return bool
     */
    public function containsAll(iterable $items): bool
    {
        if ($items instanceof Map) {
            foreach ($items as $key => $value) {
                $arrayKey = self::createValidArrayKey($key);
                return array_key_exists($arrayKey, $this->entries) && $this->entries[$arrayKey]->value === $value;
            }
        } else {
            foreach ($items as $value) {
                if (!$this->contains($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns true if the dictionary contains the specified key.
     *
     * @param mixed $key The key to lookup.
     *
     * @return bool
     * @throws InvalidArgumentException if the key is null, an array or a resource.
     */
    public function containsKey(mixed $key): bool
    {
        $arrayKey = self::createValidArrayKey($key);
        return array_key_exists($arrayKey, $this->entries);
    }

    /**
     * Returns true if the dictionary contains the specified value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function containsValue(mixed $value): bool
    {
        foreach ($this->entries as $entry) {
            if ($value === $entry->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the entries of the dictionary to an array, starting at the specified index.
     *
     * @param array $array REF: The array where the elements of the dictionary will be copied.
     * @param int $index The zero-based index in $array at which copying begins.
     *
     * @return void
     */
    public function copyTo(array &$array, int $index = 0): void
    {
        foreach ($this->entries as $entry) {
            $array[$index++] = $entry;
        }
    }

    /**
     * Returns an array containing all the elements of the collection.
     *
     * @return KeyValuePair[]
     */
    public function toArray(): array
    {
        $entries = [];
        $this->copyTo($entries);
        return $entries;
    }

    /**
     * Gets an iterator that can traverse through the entries of the dictionary.
     *
     * @return Iterator
     * @throws InvalidOperationException if the dictionary was modified within the iterator.
     */
    public function getIterator(): Iterator
    {
        $version = $this->version;
        foreach ($this->entries as $entry) {
            yield $entry->key => $entry->value;

            if ($version !== $this->version) {
                throw new InvalidOperationException('Dictionary was modified');
            }
        }
    }

    /**
     * Returns the number of entries in the dictionary.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Returns an array containing all the keys of the dictionary.
     *
     * @return array
     */
    public function getKeys(): array
    {
        $keys = [];
        foreach ($this->entries as $entry) {
            $keys[] = $entry->key;
        }

        return $keys;
    }

    /**
     * Returns an array containing all the values of the dictionary.
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->entries as $entry) {
            $values[] = $entry->value;
        }

        return $values;
    }

    /**
     * Adds a value associated to the specified key.
     *
     * @param mixed $key The key associated with the value.
     * @param mixed $value The value to add.
     *
     * @return void
     * @throws InvalidArgumentException if the key is null, an array or a resource or if the specified key already
     *     exists in the dictionary.
     */
    public function add(mixed $key, mixed $value): void
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            if (is_object($key)) {
                $key = $key::class;
            }

            throw new InvalidArgumentException(
                sprintf('Key \'%s\' exists in the dictionary', $key),
            );
        }

        $entry = new KeyValuePair();
        $entry->key = $key;
        $entry->value = $value;
        $this->entries[$arrayKey] = $entry;
        $this->size++;
        $this->version++;
    }

    /**
     * Gets the value associated to the specified key.
     *
     * @param mixed $key The key to lookup.
     *
     * @return mixed
     * @throws KeyNotFoundException if the dictionary does not contain the specified key.
     * @throws InvalidArgumentException if the key is null, an array or a resource.
     */
    public function get(mixed $key): mixed
    {
        $arrayKey = self::createValidArrayKey($key);
        if (!array_key_exists($arrayKey, $this->entries)) {
            if (is_object($key)) {
                $key = $key::class;
            }

            throw new KeyNotFoundException(
                sprintf('Key \'%s\' was not found in the dictionary', $key),
            );
        }

        return $this->entries[$arrayKey]->value;
    }

    /**
     * Sets or replaces the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value.
     * @param mixed $value The value to set.
     *
     * @return void
     * @throws InvalidArgumentException if the key is null, an array or a resource.
     */
    public function set(mixed $key, mixed $value): void
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            $entry = $this->entries[$arrayKey];
        } else {
            $entry = new KeyValuePair();
            $entry->key = $key;
            $this->entries[$arrayKey] = $entry;
            $this->size++;
        }

        $entry->value = $value;
        $this->version++;
    }

    /**
     * Removes the value associated with the specified key.
     *
     * @param mixed $key The key associated with the value to remove.
     *
     * @return bool True if the value was successfully removed; otherwise false.
     * @throws InvalidArgumentException if the key is null, an array or a resource.
     */
    public function remove(mixed $key): bool
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            unset($this->entries[$arrayKey]);
            $this->size--;
            $this->version++;
            return true;
        }

        return false;
    }

    /**
     * Returns a new dictionary containing all the entries after exchanging the keys for their values.
     *
     * @return Dictionary
     * @throws InvalidArgumentException if the key is null, an array or a resource.
     */
    public function flip(): Dictionary
    {
        $flipped = new Dictionary();
        foreach ($this->entries as $entry) {
            $k = $entry->key;
            $v = $entry->value;

            // Swap
            $flipped->add($v, $k);
        }

        return $flipped;
    }

    #region ArrayAccess methods
    public function offsetExists(mixed $offset): bool
    {
        return $this->containsKey($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }
    #endregion

    /**
     * Returns a string or integers that can be used as a key for an array.
     *
     * @param mixed $keyValue The original key to process.
     *
     * @return string|int The array key.
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    private static function createValidArrayKey(mixed $keyValue): string|int
    {
        $type = gettype($keyValue);
        return match ($type) {
            'integer' => $keyValue,
            'boolean' => 'b:' . $keyValue ? 'true' : 'false',
            'string' => 's:' . $keyValue,
            'double' => 'f:' . $keyValue,
            'object' => 'o:' . spl_object_hash($keyValue),
            default => throw new InvalidArgumentException("Value of type $type is not a valid key"),
        };
    }
}
