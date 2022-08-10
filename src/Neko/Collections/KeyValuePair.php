<?php declare(strict_types=1);
namespace Neko\Collections;

/**
 * Represents a key/value pair entry in a collection.
 */
final class KeyValuePair
{
    public mixed $key;
    public mixed $value;

    public function __construct(mixed $key = null, mixed $value = null)
    {
        if ($key !== null) {
            $this->key = $key;
            $this->value = $value;
        }
    }
}
