<?php declare(strict_types=1);
namespace Neko;

use ArithmeticError;
use DivisionByZeroError;
use InvalidArgumentException;
use function deg2rad;
use function intdiv;
use function max;
use function min;
use function rad2deg;

/**
 * Provides methods for common mathematical operations.
 */
final class Math
{
    /**
     * Returns the value clamped to the inclusive range between a minimum and a maximum.
     * When the arguments mix integers and floats, the result has the type of whichever argument is returned.
     *
     * @param int|float $value The value to clamp.
     * @param int|float $min The lower bound of the range.
     * @param int|float $max The upper bound of the range.
     *
     * @return int|float The value if it lies within the range; otherwise, the nearest bound.
     * @throws InvalidArgumentException If the minimum is greater than the maximum.
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Minimum value cannot be greater than the maximum value');
        }

        return max($min, min($value, $max));
    }

    /**
     * Returns the value clamped to the inclusive range between 0 and 1.
     *
     * @param float $value The value to clamp.
     *
     * @return float The value if it lies within the range; otherwise, the nearest bound.
     */
    public static function clamp01(float $value): float
    {
        return self::clamp($value, 0.0, 1.0);
    }

    /**
     * Returns the quotient of two integers and passes back the remainder.
     *
     * @param int $a The dividend.
     * @param int $b The divisor.
     * @param int|null $remainder Receives the remainder of the division, which has the sign of the dividend.
     *
     * @return int The quotient, truncated toward zero.
     * @throws DivisionByZeroError If the divisor is zero.
     * @throws ArithmeticError If the dividend is PHP_INT_MIN and the divisor is -1.
     */
    public static function divRem(int $a, int $b, ?int &$remainder): int
    {
        $quotient = intdiv($a, $b);
        $remainder = $a % $b;
        return $quotient;
    }

    /**
     * Returns the factorial of a non-negative integer.
     * If the result is greater than PHP_INT_MAX, which is the case for integers greater than 20 on 64-bit
     * platforms, it is returned as an approximate float, or as INF if it exceeds the range of a float.
     *
     * @param int $n The non-negative integer.
     *
     * @return int|float
     * @throws InvalidArgumentException If the integer is negative.
     */
    public static function factorial(int $n): int|float
    {
        if ($n < 0) {
            throw new InvalidArgumentException('$n must be greater than or equal to zero');
        }

        $result = 1;
        for ($i = 2; $i <= $n; ++$i) {
            $result *= $i;
        }

        return $result;
    }

    /**
     * Converts an angle from degrees to radians.
     *
     * @param float $degrees The angle in degrees.
     *
     * @return float The angle in radians.
     */
    public static function toRadians(float $degrees): float
    {
        return deg2rad($degrees);
    }

    /**
     * Converts an angle from radians to degrees.
     *
     * @param float $radians The angle in radians.
     *
     * @return float The angle in degrees.
     */
    public static function toDegrees(float $radians): float
    {
        return rad2deg($radians);
    }

    /**
     * Static class.
     */
    private function __construct()
    {
    }
}
