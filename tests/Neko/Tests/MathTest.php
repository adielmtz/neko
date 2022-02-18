<?php declare(strict_types=1);
namespace Neko\Tests;

use InvalidArgumentException;
use Neko\Math;
use PHPUnit\Framework\TestCase;
use const M_PI;

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
    #endregion
}
