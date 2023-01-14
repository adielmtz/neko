<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines an object that can be compared.
 */
interface Comparable
{
    /**
     * Compares this object with the given value.
     *
     * @param mixed $other The value to compare with.
     *
     * @return int A value less than zero if this object is less than $other.
     * A value equal to zero if this object is equal to $other.
     * A value greater than zero if this object is greater than $other.
     */
    public function compareTo(mixed $other): int;

    /**
     * Returns true if this object equals the given value.
     *
     * @param mixed $other The value to compare with.
     *
     * @return bool
     */
    public function equals(mixed $other): bool;
}
