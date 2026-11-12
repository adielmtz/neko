<?php declare(strict_types=1);
namespace Neko;

use InvalidArgumentException;

/**
 * Defines the common interface for objects that impose a total order on instances of their type.
 *
 * Implementations must satisfy the following rules, where $x, $y and $z are instances of a comparable type and
 * sgn() returns -1, 0 or 1 according to the sign of its argument:
 *
 * - Reflexivity: $x->compareTo($x) returns zero; an object is always equal in order to itself.
 * - Antisymmetry: sgn($x->compareTo($y)) === -sgn($y->compareTo($x)).
 * - Transitivity: If $x->compareTo($y) > 0 and $y->compareTo($z) > 0, then $x->compareTo($z) > 0.
 * - Substitutability: If $x->compareTo($y) returns zero, then sgn($x->compareTo($z)) === sgn($y->compareTo($z))
 *   for every $z.
 * - Consistency: Repeated calls to $x->compareTo($y) return the same result, provided neither object is modified.
 * - Null ordering: $x->compareTo(null) returns a value greater than zero; null precedes every non-null instance.
 *
 * Because every comparable object is also equatable, it is strongly recommended, but not required, that the
 * ordering be consistent with equality: that is, $x->compareTo($y) returns zero if and only if $x->equals($y)
 * is true. Types whose ordering is not consistent with equality should state so in their documentation.
 */
interface Comparable extends Equatable
{
    /**
     * Compares this object with another value to determine their relative order.
     *
     * @param mixed $other The value to compare with this object.
     *
     * @return int A value less than zero if this object precedes the other value, zero if both occupy the same
     * position in the order, or a value greater than zero if this object follows the other value.
     * @throws InvalidArgumentException If the other value is not null and cannot be compared with this object.
     */
    public function compareTo(mixed $other): int;
}
