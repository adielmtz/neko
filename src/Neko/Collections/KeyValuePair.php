<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a key/value pair entry in a map.
 */
final class KeyValuePair
{
    public readonly mixed $key;
    public mixed $value;

    /**
     * KeyValuePair constructor.
     *
     * @param mixed $key The key of the map.
     * @param mixed|null $value The value associated with the key.
     */
    public function __construct(mixed $key, mixed $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
