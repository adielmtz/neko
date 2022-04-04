<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a key/value pair entry in a collection.
 */
final class KeyValuePair
{
    private mixed $key;
    private mixed $value;

    /**
     * Gets the key.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * Sets the key.
     *
     * @param mixed $key
     */
    public function setKey(mixed $key): void
    {
        $this->key = $key;
    }

    /**
     * Gets the value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sets the value.
     *
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
