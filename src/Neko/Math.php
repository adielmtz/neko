<?php declare(strict_types=1);
namespace Neko;

use InvalidArgumentException;
use function intdiv;

/**
 * Contains methods for mathematical operations.
 */
final class Math
{
    /**
     * Returns the value clamped to the inclusive range of $min and $max.
     *
     * @param int|float $value The value to clamp.
     * @param int|float $min The minimum value.
     * @param int|float $max The maximum value.
     *
     * @return int|float
     * @throws InvalidArgumentException if the minimum value is greater than the maximum value.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Minimum value cannot be greater than the maximum value');
        }

        if ($value > $max) {
            return $max;
        }

        if ($value < $min) {
            return $min;
        }

        return $value;
    }

    /**
     * Returns the value clamped to the inclusive range of 0 and 1.
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
     * Calculates the quotient and remainder of two numbers.
     *
     * @param int $a The dividend.
     * @param int $b The divisor.
     * @param int|null &$result The resulting remainder..
     *
     * @return int The quotient.
     */
    public static function divRem(int $a, int $b, ?int &$result): int
    {
        $div = intdiv($a, $b);
        $result = $a - ($div * $b);
        return $div;
    }

    /**
     * Calculates the factorial of the given value.
     *
     * @param int $n The value to calculate its factorial.
     *
     * @return int
     * @throws InvalidArgumentException if the $n is negative.
     */
    public static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Value must be greater than or equal to zero');
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
