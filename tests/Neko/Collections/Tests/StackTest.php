<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\Stack;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;
use function var_dump;

final class StackTest extends TestCase
{
    public function testEmpty(): Stack
    {
        $stack = new Stack();
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
        return $stack;
    }

    /**
     * @depends testEmpty
     */
    public function testPush(Stack $stack): Stack
    {
        $value = 'Watame';
        $stack->push($value);
        $this->assertSame($value, $stack->peek());
        $this->assertSame(1, $stack->count());
        $this->assertFalse($stack->isEmpty());
        return $stack;
    }

    public function testContains(): void
    {
        $stack = new Stack(['a', 'b', 'c']);
        $this->assertTrue($stack->contains('b'));
        $this->assertFalse($stack->contains('z'));
    }

    /**
     * @depends testPush
     */
    public function testPop(Stack $stack): void
    {
        $this->assertSame('Watame', $stack->pop());
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
    }

    public function testPop_ThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->pop();
    }

    public function testTryPop_Success(): Stack
    {
        $stack = new Stack();
        $stack->push('Watame');

        $this->assertTrue($stack->tryPop($result));
        $this->assertSame('Watame', $result);
        $this->assertSame(0, $stack->count());
        return $stack;
    }

    /**
     * @depends testTryPop_Success
     */
    public function testTryPop_Failure(Stack $stack): void
    {
        $this->assertFalse($stack->tryPop($result));
        $this->assertNull($result);
    }

    public function testTryPeek_Success(): void
    {
        $stack = new Stack();
        $stack->push('Botan');

        $this->assertTrue($stack->tryPeek($result));
        $this->assertSame('Botan', $result);
        $this->assertSame(1, $stack->count());
    }
}
