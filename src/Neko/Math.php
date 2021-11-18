<?php declare(strict_types=1);
namespace Neko;

use InvalidArgumentException;
use function intdiv;
use function max;
use function min;

/**
 * Contains methods for mathematical operations.
 */
final class Math
{
    /**
     * Clamps the value to a given range.
     *
     * @param int|float $value The value to clamp.
     * @param int|float $min The minimum value.
     * @param int|float $max The maximum value.
     *
     * @return int|float
     * @throws InvalidArgumentException If the minimum value is greater than the maximum value.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($min > $max) {
            throw new InvalidArgumentException('The $min cannot be greater than $max');
        }

        return max($min, min($max, $value));
    }

    /**
     * Calculates the factorial of the given value.
     *
     * @param int $n The base value.
     *
     * @return int
     * @throws InvalidArgumentException If $n is negative.
     */
    public static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Base value must be equal to or greater than zero');
        }

        $result = 1;
        for (; $n > 0; $n--) {
            $result *= $n;
        }

        return $result;
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
