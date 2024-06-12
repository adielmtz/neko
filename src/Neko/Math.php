<?php declare(strict_types=1);
namespace Neko;

use DivisionByZeroError;
use InvalidArgumentException;
use function intdiv;
use function max;
use function min;
use const M_PI;

/**
 * Provides methods for common mathematical operations.
 */
final class Math
{
    /**
     * Returns the value clamped to the inclusive range of min and max.
     *
     * @param int|float $value The value to be clamped.
     * @param int|float $min The lower bound of the range.
     * @param int|float $max The upper bound of the range.
     *
     * @return int|float
     * @throws InvalidArgumentException if `$min > $max`.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Minimum value cannot be greater than the maximum value');
        }

        return max($min, min($value, $max));
    }

    /**
     * Returns the value clamped to the inclusive range of 0 and 1.
     *
     * @param float $value The value to be clamped.
     *
     * @return float
     */
    public static function clamp01(float $value): float
    {
        return self::clamp($value, 0.0, 1.0);
    }

    /**
     * Calculates the quotient and remainder of two numbers.
     *
     * @param int $a The dividend.
     * @param int $b The divisor.
     * @param int|null $result OUT: the remainder.
     *
     * @return int The quotient of the division.
     * @throws DivisionByZeroError if the divisor is zero.
     */
    public static function divRem(int $a, int $b, ?int &$result): int
    {
        $div = intdiv($a, $b);
        $result = $a - ($div * $b);
        return $div;
    }

    /**
     * Calculates the $n-th factorial.
     *
     * @param int $n The value to calculate its factorial.
     *
     * @return int
     * @throws InvalidArgumentException if $n is negative.
     */
    public static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new InvalidArgumentException('$n must be greater than or equal to zero');
        }

        $result = 1;
        while ($n > 0) {
            $result *= $n;
            $n--;
        }

        return $result;
    }

    /**
     * Converts an angle in degrees to its equivalent in radians.
     *
     * @param float $degrees The angle in degrees.
     *
     * @return float
     */
    public static function toRadians(float $degrees): float
    {
        return $degrees * (M_PI / 180);
    }

    /**
     * Converts an angle in radians to its equivalent in degrees.
     *
     * @param float $radians The angle in radians.
     *
     * @return float
     */
    public static function toDegrees(float $radians): float
    {
        return $radians * (180 / M_PI);
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
