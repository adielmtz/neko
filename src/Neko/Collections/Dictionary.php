<?php declare(strict_types=1);
namespace Neko\Collections;

use ArrayAccess;
use InvalidArgumentException;
use Neko\UnsupportedOperationException;
use Traversable;
use function array_key_exists;
use function function_exists;
use function gettype;
use function spl_object_hash;

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
     * @throws UnsupportedOperationException
     */
    public function __construct(?iterable $items = null)
    {
        if (!function_exists('spl_object_hash')) {
            throw new UnsupportedOperationException('Dictionary class requires spl_object_hash function');
        }

        if ($items !== null) {
            foreach ($items as $key => $value) {
                $this->add($key, $value);
            }
        }
    }

    /**
     * Returns true if the dictionary is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    /**
     * Removes all values from the dictionary.
     */
    public function clear(): void
    {
        $this->entries = [];
        $this->length = 0;
        $this->version++;
    }

    /**
     * Returns true if the dictionary contains the given value.
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
     * Returns true if the collection contains the specified key.
     *
     * @param mixed $key The key to search.
     *
     * @return bool
     */
    public function containsKey(mixed $key): bool
    {
        $arrayKey = self::createValidArrayKey($key);
        return array_key_exists($arrayKey, $this->entries);
    }

    /**
     * Returns true if the collection contains the specified value.
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
     * @param array $destination The destination array.
     * @param int $index The index in $destination at which copy begins.
     */
    public function copyTo(array &$destination, int $index = 0): void
    {
        foreach ($this->entries as $entry) {
            $destination[$index++] = $entry;
        }
    }

    /**
     * Returns a one-dimension array containing all the entries in the collection.
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
     * Gets an iterator instance for the list.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new KeyValuePairIterator($this->entries, $this->version);
    }

    /**
     * Returns the number of values in the dictionary.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length;
    }

    /**
     * Gets an array with the collection keys.
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
     * Gets an array with the collection values.
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
     * Adds a key and value to the dictionary.
     * Throws an exception if a value with the specified key already exists.
     *
     * @param mixed $key The key to add.
     * @param mixed $value The value add.
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $key, mixed $value): void
    {
        $arrayKey = self::createValidArrayKey($key);
        if (array_key_exists($arrayKey, $this->entries)) {
            throw new InvalidArgumentException('The key already exists');
        }

        $entry = new KeyValuePair();
        $entry->setKey($key);
        $entry->setValue($value);
        $this->entries[$arrayKey] = $entry;
        $this->length++;
        $this->version++;
    }

    /**
     * Gets the value associated to the specified key.
     *
     * @param mixed $key The key of the value to get.
     *
     * @return mixed
     * @throws KeyNotFoundException If the key is not found in the dictionary.
     * @throws InvalidArgumentException
     */
    public function get(mixed $key): mixed
    {
        $arrayKey = self::createValidArrayKey($key);
        if (!array_key_exists($arrayKey, $this->entries)) {
            throw new KeyNotFoundException('The given key was not found in the dictionary');
        }

        return $this->entries[$arrayKey]->getValue();
    }

    /**
     * Sets the value associated to the specified key.
     *
     * @param mixed $key The key of the value to set.
     * @param mixed $value The value associated to the specified key.
     *
     * @return void
     * @throws InvalidArgumentException
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
     * Removes the value associated to the specified key in the collection.
     *
     * @param mixed $key The key of the value to remove.
     *
     * @return bool True if the value was removed, false otherwise (key not found).
     * @throws InvalidArgumentException
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
     * Returns a new dictionary with the keys and values exchanged.
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

    /**
     * Creates a string or int that can be used as array key.
     *
     * @param mixed $keyValue The original key to process.
     *
     * @return string|int The array key.
     * @throws InvalidArgumentException If the key is of an invalid type.
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
}
