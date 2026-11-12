<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Neko\Collections\ArrayList;
use Neko\Collections\ConcurrentMutationException;
use Neko\Collections\Dictionary;
use Neko\Collections\Stack;
use Neko\InvalidOperationException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use stdClass;
use function iterator_to_array;
use function range;
use function serialize;
use function unserialize;

final class StackTest extends TestCase
{
    public function testImplementedInterfaces(): void
    {
        $stack = new Stack();
        $this->assertInstanceOf(Countable::class, $stack);
        $this->assertInstanceOf(IteratorAggregate::class, $stack);
    }

    public function testConstructorWithoutArgument(): void
    {
        $stack = new Stack();
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
        $this->assertSame([], $stack->toArray());
    }

    public function testConstructorWithNullArgument(): void
    {
        $stack = new Stack(null);
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
    }

    public function testConstructorWithEmptyArrayArgument(): void
    {
        $stack = new Stack([]);
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
        $this->assertSame([], $stack->toArray());
    }

    public function testConstructorWithArrayArgument(): void
    {
        $stack = new Stack(['X', 'Y', 'Z']);
        $this->assertSame(3, $stack->count());
        $this->assertSame('Z', $stack->pop());
        $this->assertSame('Y', $stack->pop());
        $this->assertSame('X', $stack->pop());
    }

    public function testConstructorWithAssociativeArrayArgumentDiscardsKeys(): void
    {
        $stack = new Stack(['a' => 1, 'b' => 2, 10 => 3]);
        $this->assertSame(3, $stack->count());
        $this->assertSame([3, 2, 1], $stack->toArray());
        $stack->push(4);
        $this->assertSame(4, $stack->pop());
        $this->assertSame(3, $stack->pop());
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

    public function testConstructorWithIteratorArgument(): void
    {
        $stack = new Stack(new ArrayIterator(['X', 'Y']));
        $this->assertSame(['Y', 'X'], $stack->toArray());
    }

    public function testConstructorWithGeneratorArgument(): void
    {
        $generator = (function () {
            yield 'k1' => 'X';
            yield 'k1' => 'Y';
            yield 'k1' => 'Z';
        })();
        $stack = new Stack($generator);
        $this->assertSame(3, $stack->count());
        $this->assertSame(['Z', 'Y', 'X'], $stack->toArray());
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

    public function testConstructorDoesNotAliasSourceCollection(): void
    {
        $source = new ArrayList(['X', 'Y']);
        $stack = new Stack($source);
        $source->add('Z');

        $this->assertSame(2, $stack->count());
        $this->assertFalse($stack->contains('Z'));
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

    #[Depends('testSerialize')]
    public function testUnserialize(string $serialized): Stack
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Stack::class, $restored);
        $this->assertSame(3, $restored->count());
        return $restored;
    }

    #[Depends('testUnserialize')]
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

    #[Depends('testSerializeEmptyStack')]
    public function testUnserializeEmptyStack(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Stack::class, $restored);
        $this->assertSame(0, $restored->count());
        $this->assertTrue($restored->isEmpty());
    }

    public function testSerializeRoundTripAfterPopsKeepsStackUsable(): void
    {
        $stack = new Stack(['A', 'B', 'C', 'D']);
        $stack->pop();
        $stack->pop();

        $restored = unserialize(serialize($stack));
        $this->assertInstanceOf(Stack::class, $restored);
        $this->assertSame(2, $restored->count());
        $this->assertSame(['B', 'A'], $restored->toArray());

        $restored->push('E');
        $this->assertSame('E', $restored->peek());
        $this->assertSame(['E', 'B', 'A'], $restored->toArray());
        $this->assertSame(['E', 'B', 'A'], iterator_to_array($restored, false));
        $this->assertSame('E', $restored->pop());
        $this->assertSame('B', $restored->pop());
        $this->assertSame('A', $restored->pop());
        $this->assertTrue($restored->isEmpty());
    }

    public function testSerializeRoundTripAfterPoppingEverything(): void
    {
        $stack = new Stack(['A', 'B']);
        $stack->pop();
        $stack->pop();

        $restored = unserialize(serialize($stack));
        $this->assertTrue($restored->isEmpty());
        $restored->push('X');
        $this->assertSame('X', $restored->peek());
        $this->assertSame(['X'], $restored->toArray());
    }

    public function testGetIteratorYieldsItemsInLastInFirstOutOrder(): void
    {
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $stack->push('C');

        $this->assertSame(['C', 'B', 'A'], iterator_to_array($stack, false));
    }

    public function testGetIteratorOnEmptyStack(): void
    {
        $this->assertSame([], iterator_to_array(new Stack(), false));
    }

    public function testIterationDoesNotConsumeItems(): void
    {
        $stack = new Stack(['A', 'B', 'C']);

        $first = iterator_to_array($stack, false);
        $second = iterator_to_array($stack, false);

        $this->assertSame(['C', 'B', 'A'], $first);
        $this->assertSame($first, $second);
        $this->assertSame(3, $stack->count());
        $this->assertSame('C', $stack->peek());
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);
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

    public function testIteratorThrowsExceptionIfPoppedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $stack = new Stack(['A', 'B', 'C']);

        foreach ($stack as $ignored) {
            $stack->pop();
        }
    }

    public function testIteratorThrowsExceptionIfTryPoppedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $stack = new Stack(['A', 'B', 'C']);

        foreach ($stack as $ignored) {
            $stack->tryPop($result);
        }
    }

    public function testIteratorThrowsExceptionIfClearedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $stack = new Stack(['A', 'B', 'C']);

        foreach ($stack as $ignored) {
            $stack->clear();
        }
    }

    public function testIteratorDoesNotThrowWhenOnlyReading(): void
    {
        $stack = new Stack(['A', 'B', 'C']);
        $seen = [];
        foreach ($stack as $item) {
            $stack->peek();
            $stack->tryPeek($top);
            $stack->contains($item);
            $stack->toArray();
            $stack->count();
            $seen[] = $item;
        }
        $this->assertSame(['C', 'B', 'A'], $seen);
    }

    public function testEmpty(): Stack
    {
        $stack = new Stack();
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
        return $stack;
    }

    #[Depends('testEmpty')]
    public function testPush(Stack $stack): Stack
    {
        $value = 'First';
        $stack->push($value);
        $this->assertSame($value, $stack->peek());
        $this->assertSame(1, $stack->count());
        $this->assertFalse($stack->isEmpty());
        return $stack;
    }

    public function testPushAcceptsNullAndMixedTypes(): void
    {
        $object = new stdClass();
        $stack = new Stack();
        $stack->push(null);
        $stack->push(1);
        $stack->push('1');
        $stack->push($object);

        $this->assertSame(4, $stack->count());
        $this->assertSame([$object, '1', 1, null], $stack->toArray());
        $this->assertSame($object, $stack->pop());
        $this->assertSame('1', $stack->pop());
        $this->assertSame(1, $stack->pop());
        $this->assertNull($stack->pop());
        $this->assertTrue($stack->isEmpty());
    }

    public function testPushAllowsDuplicates(): void
    {
        $stack = new Stack();
        $stack->push('A');
        $stack->push('A');
        $this->assertSame(2, $stack->count());
        $this->assertSame(['A', 'A'], $stack->toArray());
    }

    public function testContains(): void
    {
        $stack = new Stack(['a', 'b', 'c']);
        $this->assertTrue($stack->contains('a'));
        $this->assertTrue($stack->contains('b'));
        $this->assertTrue($stack->contains('c'));
        $this->assertFalse($stack->contains('z'));
    }

    public function testContainsOnEmptyStack(): void
    {
        $stack = new Stack();
        $this->assertFalse($stack->contains('a'));
        $this->assertFalse($stack->contains(null));
    }

    public function testContainsUsesStrictComparison(): void
    {
        $stack = new Stack([1, 0, false]);
        $this->assertTrue($stack->contains(1));
        $this->assertFalse($stack->contains('1'));
        $this->assertFalse($stack->contains(1.0));
        $this->assertFalse($stack->contains(true));
        $this->assertFalse($stack->contains(null));
        $this->assertFalse($stack->contains(''));
        $this->assertTrue($stack->contains(0));
        $this->assertTrue($stack->contains(false));
    }

    public function testContainsComparesObjectsByIdentity(): void
    {
        $object = new stdClass();
        $stack = new Stack([$object]);
        $this->assertTrue($stack->contains($object));
        $this->assertFalse($stack->contains(new stdClass()));
    }

    public function testContainsNull(): void
    {
        $stack = new Stack(['a', null]);
        $this->assertTrue($stack->contains(null));
    }

    public function testContainsReturnsFalseForPoppedItems(): void
    {
        $stack = new Stack(['a', 'b', 'c']);
        $stack->pop();
        $this->assertFalse($stack->contains('c'));
        $this->assertTrue($stack->contains('b'));
        $stack->tryPop($result);
        $this->assertFalse($stack->contains('b'));
        $this->assertTrue($stack->contains('a'));
    }

    #[Depends('testPush')]
    public function testPop(Stack $stack): void
    {
        $this->assertSame('First', $stack->pop());
        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
    }

    public function testPopReturnsItemsInLastInFirstOutOrder(): void
    {
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $this->assertSame('B', $stack->pop());
        $stack->push('C');
        $this->assertSame('C', $stack->pop());
        $this->assertSame('A', $stack->pop());
        $this->assertTrue($stack->isEmpty());
    }

    public function testPop_ThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->pop();
    }

    public function testPop_ThrowsExceptionAfterPoppingEverything(): void
    {
        $stack = new Stack(['A']);
        $stack->pop();
        $this->expectException(InvalidOperationException::class);
        $stack->pop();
    }

    public function testPeek(): void
    {
        $stack = new Stack(['A', 'B']);
        $this->assertSame('B', $stack->peek());
        $this->assertSame('B', $stack->peek());
        $this->assertSame(2, $stack->count());
        $stack->pop();
        $this->assertSame('A', $stack->peek());
    }

    public function testPeek_ThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->peek();
    }

    public function testPeek_ThrowsExceptionAfterClear(): void
    {
        $stack = new Stack(['A']);
        $stack->clear();
        $this->expectException(InvalidOperationException::class);
        $stack->peek();
    }

    public function testTryPop_Success(): Stack
    {
        $stack = new Stack();
        $stack->push('MyValue');

        $this->assertTrue($stack->tryPop($result));
        $this->assertSame('MyValue', $result);
        $this->assertSame(0, $stack->count());
        return $stack;
    }

    #[Depends('testTryPop_Success')]
    public function testTryPop_Failure(Stack $stack): void
    {
        $this->assertFalse($stack->tryPop($result));
        $this->assertNull($result);
    }

    public function testTryPop_FailureOverwritesResultWithNull(): void
    {
        $stack = new Stack();
        $result = 'previous';
        $this->assertFalse($stack->tryPop($result));
        $this->assertNull($result);
    }

    public function testTryPop_ReturnsItemsInLastInFirstOutOrder(): void
    {
        $stack = new Stack(['A', 'B']);
        $this->assertTrue($stack->tryPop($result));
        $this->assertSame('B', $result);
        $this->assertTrue($stack->tryPop($result));
        $this->assertSame('A', $result);
        $this->assertFalse($stack->tryPop($result));
        $this->assertNull($result);
        $this->assertSame(0, $stack->count());
    }

    public function testTryPop_SucceedsWithNullItem(): void
    {
        $stack = new Stack([null]);
        $result = 'previous';
        $this->assertTrue($stack->tryPop($result));
        $this->assertNull($result);
        $this->assertTrue($stack->isEmpty());
    }

    public function testTryPeek_Success(): void
    {
        $stack = new Stack();
        $stack->push('MyValue');

        $this->assertTrue($stack->tryPeek($result));
        $this->assertSame('MyValue', $result);
        $this->assertSame(1, $stack->count());
    }

    public function testTryPeek_ReturnsTopItem(): void
    {
        $stack = new Stack(['A', 'B', 'C']);
        $this->assertTrue($stack->tryPeek($result));
        $this->assertSame('C', $result);
        $this->assertSame(3, $stack->count());
    }

    public function testTryPeek_Failure(): void
    {
        $stack = new Stack();
        $result = 'previous';
        $this->assertFalse($stack->tryPeek($result));
        $this->assertNull($result);
        $this->assertSame(0, $stack->count());
    }

    public function testToArrayReturnsItemsInLastInFirstOutOrder(): void
    {
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $stack->push('C');

        $this->assertSame(['C', 'B', 'A'], $stack->toArray());
        $this->assertSame(3, $stack->count());
    }

    public function testToArrayIsIsolatedCopy(): void
    {
        $stack = new Stack(['A', 'B']);
        $array = $stack->toArray();
        $array[] = 'Z';
        $stack->push('C');

        $this->assertSame(['B', 'A', 'Z'], $array);
        $this->assertSame(['C', 'B', 'A'], $stack->toArray());
    }

    public function testToArrayOnEmptyStack(): void
    {
        $this->assertSame([], (new Stack())->toArray());
    }

    public function testClear(): void
    {
        $stack = new Stack(['A', 'B', 'C']);
        $stack->clear();

        $this->assertSame(0, $stack->count());
        $this->assertTrue($stack->isEmpty());
        $this->assertSame([], $stack->toArray());
        $this->assertSame([], iterator_to_array($stack, false));
        $this->assertFalse($stack->contains('A'));
        $this->assertFalse($stack->tryPeek($result));
        $this->assertNull($result);
    }

    public function testClearOnEmptyStack(): void
    {
        $stack = new Stack();
        $stack->clear();
        $this->assertTrue($stack->isEmpty());
    }

    public function testClearThenReuse(): void
    {
        $stack = new Stack(['A', 'B', 'C']);
        $stack->clear();
        $stack->push('X');
        $stack->push('Y');

        $this->assertSame(2, $stack->count());
        $this->assertSame('Y', $stack->peek());
        $this->assertSame(['Y', 'X'], $stack->toArray());
        $this->assertSame(['Y', 'X'], iterator_to_array($stack, false));
        $this->assertFalse($stack->contains('A'));
        $this->assertSame('Y', $stack->pop());
        $this->assertSame('X', $stack->pop());
        $this->assertTrue($stack->isEmpty());
    }

    public function testPopEverythingThenPushAgain(): void
    {
        $stack = new Stack(['A', 'B']);
        $stack->pop();
        $stack->pop();
        $stack->push('C');

        $this->assertSame(1, $stack->count());
        $this->assertSame('C', $stack->peek());
        $this->assertSame(['C'], $stack->toArray());
        $this->assertSame(['C'], iterator_to_array($stack, false));
    }

    public function testManyInterleavedPushesAndPops(): void
    {
        $stack = new Stack();
        foreach (range(1, 1000) as $i) {
            $stack->push($i);
        }
        for ($i = 1000; $i > 10; --$i) {
            $this->assertSame($i, $stack->pop());
        }
        foreach (range(2001, 2005) as $i) {
            $stack->push($i);
        }

        $expected = [2005, 2004, 2003, 2002, 2001, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1];
        $this->assertSame(15, $stack->count());
        $this->assertSame(2005, $stack->peek());
        $this->assertSame($expected, $stack->toArray());
        $this->assertSame($expected, iterator_to_array($stack, false));
        $this->assertTrue($stack->contains(10));
        $this->assertFalse($stack->contains(11));
        $this->assertFalse($stack->contains(1000));
    }
}
