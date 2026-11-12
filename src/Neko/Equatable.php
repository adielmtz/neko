<?php declare(strict_types=1);
namespace Neko;

/**
 * Defines the common interface for objects that can determine whether they are equal to another value.
 *
 * Implementations must define an equivalence relation that satisfies the following rules, where $x, $y and $z
 * are non-null instances:
 *
 * - Reflexivity: $x->equals($x) is true.
 * - Symmetry: $x->equals($y) is true if and only if $y->equals($x) is true.
 * - Transitivity: If $x->equals($y) and $y->equals($z) are true, then $x->equals($z) is true.
 * - Consistency: Repeated calls to $x->equals($y) return the same result, provided neither object is modified.
 * - Non-nullity: $x->equals(null) is false.
 *
 * Values of an incompatible type are never equal to the instance; equals() returns false instead of throwing.
 */
interface Equatable
{
    /**
     * Determines whether this object is equal to another value.
     *
     * @param mixed $other The value to compare with this object.
     *
     * @return bool True if this object is equal to the other value; otherwise, false.
     */
    public function equals(mixed $other): bool;
}
