<?php declare(strict_types=1);
namespace Neko\Tests;

use ArithmeticError;
use DivisionByZeroError;
use InvalidArgumentException;
use Neko\Math;
use PHPUnit\Framework\TestCase;
use const INF;
use const M_PI;
use const PHP_INT_MIN;

final class MathTest extends TestCase
{
    #region Math::clamp() tests
    public function testClampReturnsTheValue(): void
    {
        $min = 50;
        $max = 100;
        $val = 60; // Expected
        $this->assertSame($val, Math::clamp($val, $min, $max));
    }

    public function testClampReturnsTheMinimumValue(): void
    {
        $min = 50; // Expected
        $max = 100;
        $val = 1;
        $this->assertSame($min, Math::clamp($val, $min, $max));
    }

    public function testClampReturnsTheMaximumValue(): void
    {
        $min = 50;
        $max = 100; // Expected
        $val = 500;
        $this->assertSame($max, Math::clamp($val, $min, $max));
    }

    public function testClampThrowsExceptionIfMinIsGreaterThanMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $min = 100;
        $max = 50;
        $val = 1;
        Math::clamp($val, $min, $max);
    }
    #endregion

    #region Math::clamp01() tests
    public function testClamp01(): void
    {
        $value = 0.3;
        $this->assertSame($value, Math::clamp01($value));
    }

    public function testClamp01Returns1(): void
    {
        $this->assertSame(1.0, Math::clamp01(M_PI));
    }

    public function testClamp01Returns0(): void
    {
        $this->assertSame(0.0, Math::clamp01(-M_PI));
    }
    #endregion

    #region Math::divRem() tests
    public function testDivRemBasic(): void
    {
        $div = Math::divRem(4, 2, $remainder);
        $this->assertSame(2, $div);
        $this->assertSame(0, $remainder);

        $div = Math::divRem(1, 3, $remainder);
        $this->assertSame(0, $div);
        $this->assertSame(1, $remainder);

        $div = Math::divRem(0, 1, $remainder);
        $this->assertSame(0, $div);
        $this->assertSame(0, $remainder);
    }

    public function testDivRemTruncatesTowardZeroWithNegativeOperands(): void
    {
        $this->assertSame(-2, Math::divRem(-7, 3, $remainder));
        $this->assertSame(-1, $remainder);

        $this->assertSame(-2, Math::divRem(7, -3, $remainder));
        $this->assertSame(1, $remainder);

        $this->assertSame(2, Math::divRem(-7, -3, $remainder));
        $this->assertSame(-1, $remainder);
    }

    public function testDivRemThrowsDivisionByZeroError(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Math::divRem(1, 0, $remainder);
    }

    public function testDivRemThrowsArithmeticErrorOnOverflow(): void
    {
        $this->expectException(ArithmeticError::class);
        Math::divRem(PHP_INT_MIN, -1, $remainder);
    }
    #endregion

    #region Math::factorial() tests
    public function testFactorialWithFirst10Values(): void
    {
        $factorial_table = [
            0 => 1,
            1 => 1,
            2 => 2,
            3 => 6,
            4 => 24,
            5 => 120,
            6 => 720,
            7 => 5040,
            8 => 40320,
            9 => 362880,
            10 => 3628800,
        ];

        foreach ($factorial_table as $n => $expected) {
            $this->assertSame($expected, Math::factorial($n));
        }
    }

    public function testFactorialThrowsExceptionIfTheArgumentIsANegativeNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Math::factorial(-1);
    }

    public function testFactorialReturnsTheLargestRepresentableValue(): void
    {
        $this->assertSame(2432902008176640000, Math::factorial(20));
    }

    public function testFactorialReturnsFloatOnOverflow(): void
    {
        $this->assertSame(5.109094217170944E+19, Math::factorial(21));
        $this->assertSame(INF, Math::factorial(171));
    }
    #endregion

    #region Math::toRadians() and Math::toDegrees() tests
    public function testToRadians(): void
    {
        $this->assertSame(0.0, Math::toRadians(0.0));
        $this->assertSame(M_PI, Math::toRadians(180.0));
        $this->assertEqualsWithDelta(M_PI / 2, Math::toRadians(90.0), 1e-12);
    }

    public function testToDegrees(): void
    {
        $this->assertSame(0.0, Math::toDegrees(0.0));
        $this->assertSame(180.0, Math::toDegrees(M_PI));
        $this->assertEqualsWithDelta(90.0, Math::toDegrees(M_PI / 2), 1e-12);
    }
    #endregion
}
