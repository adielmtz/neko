<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use InvalidArgumentException;
use Neko\InvalidOperationException;
use Neko\NotSupportedException;
use Traversable;
use function array_key_exists;
use function function_exists;
use function gettype;
use function is_object;
use function spl_object_hash;
use function sprintf;

/**
 * Represents a collection of keys and values.
 */
final class Dictionary implements ArrayAccess, KeyValuePairCollection
{
    /**
     * @var KeyValuePair[]
     */
    private array $entries = [];
    private int $length = 0;
    private int $version = 0;

    /**
     * Dictionary constructor.
     *
     * @param iterable|null $items A collection of values that will be copied to the dictionary.
     *
     * @throws NotSupportedException
     */
    public function __construct(?iterable $items = null)
    {
        if (!function_exists('spl_object_hash')) {
            throw new NotSupportedException(
                'Dictionary class requires spl_object_hash() function which is not available for this PHP installation'
            );
        }

        if ($items !== null) {
            foreach ($items as $key => $value) {
                $this->add($key, $value);
            }
        }
    }

    /**
     * Returns true if the dictionary contains no entries.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all entries from the dictionary.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->entries = [];
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the dictionary contains a specific entry.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        if ($value instanceof KeyValuePair) {
            foreach ($this->entries as $entry) {
                if ($value === $entry) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if the dictionary contains a specific key.
     *
     * @param mixed $key The key to search.
     *
     * @return bool
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function containsKey(mixed $key): bool
    {
        $arrayKey = self::createValidArrayKey($key);
        return array_key_exists($arrayKey, $this->entries);
    }

    /**
     * Returns true if the dictionary contains a specific value.
     *
     * @param mixed $value The value to search.
     *
     * @return bool
     */
    public function containsValue(mixed $value): bool
    {
        foreach ($this->entries as $entry) {
            if ($value === $entry->getValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the entries of the dictionary to an array.
     *
     * @param array $array
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
     * Returns an array containing all the entries of the dictionary.
     *
     * @return array
     */
    public function toArray(): array
    {
        $entries = [];
        $this->copyTo($entries);
        return $entries;
    }

    /**
     * Returns an iterator over the entries in the list.
     *
     * @return Traversable
     * @throws InvalidOperationException
     */
    public function getIterator(): Traversable
    {
        $version = $this->version;
        foreach ($this->entries as $entry) {
            yield $entry->getKey() => $entry->getValue();

            if ($version !== $this->version) {
                throw new InvalidOperationException('Dictionary was modified');
            }
        }
    }

    /**
     * Returns the number of entries in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
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
            $keys[] = $entry->getKey();
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
            $values[] = $entry->getValue();
        }

        return $values;
    }

    /**
     * Adds a key and a value pair to the dictionary.
     *
     * @param mixed $key The key that maps to the value.
     * @param mixed $value The value of the element to add.
     *
     * @return void
     * @throws InvalidArgumentException if the key is of type array or a resource or the key already exists in the
     *     dictionary.
     */
    public function add(mixed $key, mixed $value): void
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            if (is_object($key)) {
                $key = $key::class;
            }

            throw new InvalidArgumentException(
                sprintf('Key \'%s\' exists in the dictionary', $key)
            );
        }

        $entry = new KeyValuePair();
        $entry->setKey($key);
        $entry->setValue($value);
        $this->entries[$arrayKey] = $entry;
        $this->length++;
        $this->version++;
    }

    /**
     * Returns the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value to return.
     *
     * @return mixed
     * @throws InvalidArgumentException if the key is of type array or a resource.
     * @throws KeyNotFoundException if the key does not exist in the dictionary.
     */
    public function get(mixed $key): mixed
    {
        $arrayKey = self::createValidArrayKey($key);
        if (!array_key_exists($arrayKey, $this->entries)) {
            if (is_object($key)) {
                $key = $key::class;
            }

            throw new KeyNotFoundException(
                sprintf('Key \'%s\' was not found in the dictionary', $key)
            );
        }

        return $this->entries[$arrayKey]->getValue();
    }

    /**
     * Replaces the value associated to the specified key or sets a new key and value pair to the dictionary.
     *
     * @param mixed $key The key of the value to set or replace.
     * @param mixed $value The value of the element to set or replace.
     *
     * @return void
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function set(mixed $key, mixed $value): void
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            $entry = $this->entries[$arrayKey];
        } else {
            $entry = new KeyValuePair();
            $entry->setKey($key);
            $this->entries[$arrayKey] = $entry;
            $this->length++;
        }

        $entry->setValue($value);
        $this->version++;
    }

    /**
     * Removes the value associated to the specified key.
     *
     * @param mixed $key The key associated with the value to remove.
     *
     * @return bool True if the element existed and was removed; otherwise, false.
     * @throws InvalidArgumentException if the key is of type array or a resource.
     */
    public function remove(mixed $key): bool
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            unset($this->entries[$arrayKey]);
            $this->length--;
            $this->version++;
            return true;
        }

        return false;
    }

    /**
     * Returns a new dictionary containing the entries of the dictionary by exchanging the keys with the values.
     *
     * @return Dictionary
     */
    public function flip(): Dictionary
    {
        $flipped = new Dictionary();
        foreach ($this->entries as $entry) {
            $k = $entry->getKey();
            $v = $entry->getValue();

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
