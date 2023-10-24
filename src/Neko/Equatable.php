<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines a method that can determine the equality of two objects.
 */
interface Equatable
{
    /**
     * Returns true if this object is equal to the given value.
     *
     * @param mixed $other The value to compare with.
     *
     * @return bool
     */
    public function equals(mixed $other): bool;
}
