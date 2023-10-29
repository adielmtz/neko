<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines a method for performing comparisons between types.
 */
interface Comparable
{
    /**
     * Compares this object with another value and returns an integer indicating the order.
     *
     * @param mixed $other The value to compare.
     *
     * @return int Returns a value less than zero if this instance is less than the other value.
     * Returns zero if this instance is equal to the other value.
     * Returns a value greater than zero if this instance is greater than the other value.
     */
    public function compareTo(mixed $other): int;
}
