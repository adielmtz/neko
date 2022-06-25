<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ArrayList;
use Neko\Collections\Dictionary;
use Neko\Collections\Stack;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;
use function serialize;
use function unserialize;

final class StackTest extends TestCase
{
    public function testConstructorWithArrayArgument(): void
    {
        $stack = new Stack(['X', 'Y', 'Z']);
        $this->assertSame(3, $stack->count());
        $this->assertSame('Z', $stack->pop());
        $this->assertSame('Y', $stack->pop());
        $this->assertSame('X', $stack->pop());
    }

    public function testConstructorWithIterableArgument(): void
    {
        $argument = new ArrayList(['X', 'Y', 'Z']);
        $stack = new Stack($argument);
        $stack->push('A');

        $this->assertSame(4, $stack->count());
        $this->assertSame('A', $stack->pop());
        $this->assertSame('Z', $stack->pop());
        $this->assertSame('Y', $stack->pop());
        $this->assertSame('X', $stack->pop());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary(['A' => 'a', 'B' => 'b', 'C' => 'c']);
        $stack = new Stack($argument);

        $this->assertSame(3, $stack->count());
        $this->assertSame('c', $stack->pop());
        $this->assertSame('b', $stack->pop());
        $this->assertSame('a', $stack->pop());
    }

    public function testSerialize(): string
    {
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $stack->push('C');

        $serialized = serialize($stack);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize(string $serialized): Stack
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Stack::class, $restored);
        $this->assertSame(3, $restored->count());
        return $restored;
    }

    /**
     * @depends testUnserialize
     */
    public function testUnserializedStackKeepsLastInFirstOutOrder(Stack $stack): void
    {
        $this->assertSame('C', $stack->pop());
        $this->assertSame('B', $stack->pop());
        $this->assertSame('A', $stack->pop());
    }

    public function testSerializeEmptyStack(): string
    {
        $empty = new Stack();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    /**
     * @depends testSerializeEmptyStack
     */
    public function testUnserializeEmptyStack(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Stack::class, $restored);
        $this->assertSame(0, $restored->count());
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $stack->push('C');

        foreach ($stack as $char) {
            if ($char === 'A') {
                $stack->push('D');
            }
        }
    }

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
