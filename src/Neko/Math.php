<?php declare(strict_types=1);
namespace Neko;

use InvalidArgumentException;
use function max;
use function min;

/**
 * Contains methods for mathematical operations.
 */
final class Math
{
    /**
     * Clamps the value to a given inclusive range.
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
            throw new InvalidArgumentException('$min cannot be greater than $max');
        }

        return max($min, min($max, $value));
    }

    /**
     * Clamps the value to 0 or 1 inclusive.
     *
     * @param float $value The value to clamp.
     *
     * @return float
     */
    public static function clamp01(float $value): float
    {
        if ($value > 1.0) {
            return 1.0;
        }

        if ($value < 0.0) {
            return 0.0;
        }

        return $value;
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
