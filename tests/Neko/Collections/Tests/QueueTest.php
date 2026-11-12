<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Neko\Collections\ArrayList;
use Neko\Collections\ConcurrentMutationException;
use Neko\Collections\Dictionary;
use Neko\Collections\Queue;
use Neko\InvalidOperationException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use stdClass;
use function array_shift;
use function iterator_to_array;
use function range;
use function serialize;
use function unserialize;

final class QueueTest extends TestCase
{
    public function testImplementedInterfaces(): void
    {
        $queue = new Queue();
        $this->assertInstanceOf(Countable::class, $queue);
        $this->assertInstanceOf(IteratorAggregate::class, $queue);
    }

    public function testConstructorWithoutArgument(): void
    {
        $queue = new Queue();
        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
        $this->assertSame([], $queue->toArray());
    }

    public function testConstructorWithNullArgument(): void
    {
        $queue = new Queue(null);
        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
    }

    public function testConstructorWithEmptyArrayArgument(): void
    {
        $queue = new Queue([]);
        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
        $this->assertSame([], $queue->toArray());
    }

    public function testConstructorWithArrayArgument(): void
    {
        $queue = new Queue(['X', 'Y', 'Z']);
        $this->assertSame(3, $queue->count());
        $this->assertSame('X', $queue->dequeue());
        $this->assertSame('Y', $queue->dequeue());
        $this->assertSame('Z', $queue->dequeue());
    }

    public function testConstructorWithAssociativeArrayArgumentDiscardsKeys(): void
    {
        $queue = new Queue(['a' => 1, 'b' => 2, 10 => 3]);
        $this->assertSame(3, $queue->count());
        $this->assertSame(1, $queue->peek());
        $this->assertSame([1, 2, 3], $queue->toArray());
        $this->assertSame([1, 2, 3], iterator_to_array($queue, false));
        $queue->enqueue(4);
        $this->assertSame(1, $queue->dequeue());
        $this->assertSame(2, $queue->dequeue());
        $this->assertSame(3, $queue->dequeue());
        $this->assertSame(4, $queue->dequeue());
    }

    public function testConstructorWithIteratorArgument(): void
    {
        $argument = new ArrayList(['X', 'Y', 'Z']);
        $queue = new Queue($argument);
        $queue->enqueue('A');

        $this->assertSame(4, $queue->count());
        $this->assertSame('X', $queue->dequeue());
        $this->assertSame('Y', $queue->dequeue());
        $this->assertSame('Z', $queue->dequeue());
        $this->assertSame('A', $queue->dequeue());
    }

    public function testConstructorWithArrayIteratorArgument(): void
    {
        $queue = new Queue(new ArrayIterator(['X', 'Y']));
        $this->assertSame(['X', 'Y'], $queue->toArray());
    }

    public function testConstructorWithGeneratorArgument(): void
    {
        $generator = (function () {
            yield 'k1' => 'X';
            yield 'k1' => 'Y';
            yield 'k1' => 'Z';
        })();
        $queue = new Queue($generator);
        $this->assertSame(3, $queue->count());
        $this->assertSame(['X', 'Y', 'Z'], $queue->toArray());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary(['A' => 'a', 'B' => 'b', 'C' => 'c']);
        $queue = new Queue($argument);

        $this->assertSame(3, $queue->count());
        $this->assertSame('a', $queue->dequeue());
        $this->assertSame('b', $queue->dequeue());
        $this->assertSame('c', $queue->dequeue());
    }

    public function testConstructorDoesNotAliasSourceCollection(): void
    {
        $source = new ArrayList(['X', 'Y']);
        $queue = new Queue($source);
        $source->add('Z');

        $this->assertSame(2, $queue->count());
        $this->assertFalse($queue->contains('Z'));
    }

    public function testConstructedQueueThenDequeueAndEnqueue(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $this->assertSame('A', $queue->dequeue());
        $this->assertSame('B', $queue->dequeue());
        $queue->enqueue('D');
        $queue->enqueue('E');

        $this->assertSame(3, $queue->count());
        $this->assertSame('C', $queue->peek());
        $this->assertSame(['C', 'D', 'E'], $queue->toArray());
        $this->assertSame(['C', 'D', 'E'], iterator_to_array($queue, false));
        $this->assertFalse($queue->contains('A'));
        $this->assertTrue($queue->contains('E'));
        $this->assertSame('C', $queue->dequeue());
        $this->assertSame('D', $queue->dequeue());
        $this->assertSame('E', $queue->dequeue());
        $this->assertTrue($queue->isEmpty());
    }

    public function testSerialize(): string
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $queue->enqueue('C');

        $serialized = serialize($queue);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerialize')]
    public function testUnserialize(string $serialized): Queue
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Queue::class, $restored);
        $this->assertSame(3, $restored->count());
        return $restored;
    }

    #[Depends('testUnserialize')]
    public function testUnserializedQueueKeepsFirstInFirstOutOrder(Queue $queue): void
    {
        $this->assertSame('A', $queue->dequeue());
        $this->assertSame('B', $queue->dequeue());
        $this->assertSame('C', $queue->dequeue());
    }

    public function testSerializeEmptyQueue(): string
    {
        $empty = new Queue();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerializeEmptyQueue')]
    public function testUnserializeEmptyQueue(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Queue::class, $restored);
        $this->assertSame(0, $restored->count());
        $this->assertTrue($restored->isEmpty());
    }

    public function testSerializeRoundTripAfterDequeues(): void
    {
        $queue = new Queue(['A', 'B', 'C', 'D']);
        $queue->dequeue();
        $queue->dequeue();

        $restored = unserialize(serialize($queue));
        $this->assertInstanceOf(Queue::class, $restored);
        $this->assertSame(2, $restored->count());
        $this->assertSame('C', $restored->peek());
        $this->assertSame(['C', 'D'], $restored->toArray());
        $this->assertSame(['C', 'D'], iterator_to_array($restored, false));
        $this->assertFalse($restored->contains('A'));

        $restored->enqueue('E');
        $this->assertSame(['C', 'D', 'E'], $restored->toArray());
        $this->assertSame(['C', 'D', 'E'], iterator_to_array($restored, false));
        $this->assertSame('C', $restored->dequeue());
        $this->assertSame('D', $restored->dequeue());
        $this->assertSame('E', $restored->dequeue());
        $this->assertTrue($restored->isEmpty());
    }

    public function testSerializeRoundTripAfterDequeuingEverything(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $queue->dequeue();
        $queue->dequeue();
        $queue->dequeue();

        $restored = unserialize(serialize($queue));
        $this->assertTrue($restored->isEmpty());

        $restored->enqueue('X');
        $this->assertSame(1, $restored->count());
        $this->assertSame('X', $restored->peek());
        $this->assertSame(['X'], $restored->toArray());
        $this->assertSame(['X'], iterator_to_array($restored, false));
        $this->assertSame('X', $restored->dequeue());
    }

    public function testGetIteratorYieldsItemsInFirstInFirstOutOrder(): void
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $queue->enqueue('C');

        $this->assertSame(['A', 'B', 'C'], iterator_to_array($queue, false));
    }

    public function testGetIteratorOnEmptyQueue(): void
    {
        $this->assertSame([], iterator_to_array(new Queue(), false));
    }

    public function testIterationDoesNotConsumeItems(): void
    {
        $queue = new Queue(['A', 'B', 'C']);

        $first = iterator_to_array($queue, false);
        $second = iterator_to_array($queue, false);

        $this->assertSame(['A', 'B', 'C'], $first);
        $this->assertSame($first, $second);
        $this->assertSame(3, $queue->count());
        $this->assertSame('A', $queue->peek());
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $queue->enqueue('C');
        $queue->enqueue('D');

        foreach ($queue as $char) {
            if ($char === 'D') {
                $queue->enqueue('D');
            }
        }
    }

    public function testIteratorThrowsExceptionIfDequeuedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $queue = new Queue(['A', 'B', 'C']);

        foreach ($queue as $ignored) {
            $queue->dequeue();
        }
    }

    public function testIteratorThrowsExceptionIfTryDequeuedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $queue = new Queue(['A', 'B', 'C']);

        foreach ($queue as $ignored) {
            $queue->tryDequeue($result);
        }
    }

    public function testIteratorThrowsExceptionIfClearedDuringIteration(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $queue = new Queue(['A', 'B', 'C']);

        foreach ($queue as $ignored) {
            $queue->clear();
        }
    }

    public function testIteratorDoesNotThrowWhenOnlyReading(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $seen = [];
        foreach ($queue as $item) {
            $queue->peek();
            $queue->tryPeek($head);
            $queue->contains($item);
            $queue->toArray();
            $queue->count();
            $seen[] = $item;
        }
        $this->assertSame(['A', 'B', 'C'], $seen);
    }

    public function testEmpty(): Queue
    {
        $queue = new Queue();
        $this->assertTrue($queue->isEmpty());
        $this->assertSame(0, $queue->count());
        return $queue;
    }

    #[Depends('testEmpty')]
    public function testEnqueue(Queue $queue): Queue
    {
        $value = 'First';
        $queue->enqueue($value);
        $this->assertSame($value, $queue->peek());
        $this->assertSame(1, $queue->count());
        $this->assertFalse($queue->isEmpty());
        return $queue;
    }

    public function testEnqueueAcceptsNullAndMixedTypes(): void
    {
        $object = new stdClass();
        $queue = new Queue();
        $queue->enqueue(null);
        $queue->enqueue(1);
        $queue->enqueue('1');
        $queue->enqueue($object);

        $this->assertSame(4, $queue->count());
        $this->assertSame([null, 1, '1', $object], $queue->toArray());
        $this->assertNull($queue->dequeue());
        $this->assertSame(1, $queue->dequeue());
        $this->assertSame('1', $queue->dequeue());
        $this->assertSame($object, $queue->dequeue());
        $this->assertTrue($queue->isEmpty());
    }

    public function testEnqueueAllowsDuplicates(): void
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('A');
        $this->assertSame(2, $queue->count());
        $this->assertSame(['A', 'A'], $queue->toArray());
    }

    #[Depends('testEnqueue')]
    public function testContains(Queue $queue): Queue
    {
        $this->assertTrue($queue->contains('First'));
        $this->assertFalse($queue->contains('This value is not present'));
        return $queue;
    }

    public function testContainsOnEmptyQueue(): void
    {
        $queue = new Queue();
        $this->assertFalse($queue->contains('a'));
        $this->assertFalse($queue->contains(null));
    }

    public function testContainsUsesStrictComparison(): void
    {
        $queue = new Queue([1, 0, false]);
        $this->assertTrue($queue->contains(1));
        $this->assertFalse($queue->contains('1'));
        $this->assertFalse($queue->contains(1.0));
        $this->assertFalse($queue->contains(true));
        $this->assertFalse($queue->contains(null));
        $this->assertFalse($queue->contains(''));
        $this->assertTrue($queue->contains(0));
        $this->assertTrue($queue->contains(false));
    }

    public function testContainsComparesObjectsByIdentity(): void
    {
        $object = new stdClass();
        $queue = new Queue([$object]);
        $this->assertTrue($queue->contains($object));
        $this->assertFalse($queue->contains(new stdClass()));
    }

    public function testContainsNull(): void
    {
        $queue = new Queue([null, 'a']);
        $this->assertTrue($queue->contains(null));
        $queue->dequeue();
        $this->assertFalse($queue->contains(null));
    }

    public function testContainsReturnsFalseForDequeuedItems(): void
    {
        $queue = new Queue(['a', 'b', 'c']);
        $queue->dequeue();
        $this->assertFalse($queue->contains('a'));
        $this->assertTrue($queue->contains('b'));
        $queue->tryDequeue($result);
        $this->assertFalse($queue->contains('b'));
        $this->assertTrue($queue->contains('c'));
        $queue->dequeue();
        $this->assertFalse($queue->contains('c'));
    }

    #[Depends('testContains')]
    public function testDequeue(Queue $queue): void
    {
        $this->assertSame('First', $queue->dequeue());
        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
    }

    public function testDequeueReturnsItemsInFirstInFirstOutOrder(): void
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $this->assertSame('A', $queue->dequeue());
        $queue->enqueue('C');
        $this->assertSame('B', $queue->dequeue());
        $this->assertSame('C', $queue->dequeue());
        $this->assertTrue($queue->isEmpty());
    }

    public function testDequeueThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $queue = new Queue();
        $queue->dequeue();
    }

    public function testDequeueThrowsExceptionAfterDequeuingEverything(): void
    {
        $queue = new Queue(['A']);
        $queue->dequeue();
        $this->expectException(InvalidOperationException::class);
        $queue->dequeue();
    }

    public function testPeek(): void
    {
        $queue = new Queue(['A', 'B']);
        $this->assertSame('A', $queue->peek());
        $this->assertSame('A', $queue->peek());
        $this->assertSame(2, $queue->count());
        $queue->dequeue();
        $this->assertSame('B', $queue->peek());
    }

    public function testPeekThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $queue = new Queue();
        $queue->peek();
    }

    public function testPeekThrowsExceptionAfterDequeuingEverything(): void
    {
        $queue = new Queue(['A', 'B']);
        $queue->dequeue();
        $queue->dequeue();
        $this->expectException(InvalidOperationException::class);
        $queue->peek();
    }

    public function testPeekThrowsExceptionAfterClear(): void
    {
        $queue = new Queue(['A']);
        $queue->clear();
        $this->expectException(InvalidOperationException::class);
        $queue->peek();
    }

    public function testTryDequeue_Success(): Queue
    {
        $queue = new Queue();
        $queue->enqueue('MyValue');

        $this->assertTrue($queue->tryDequeue($result));
        $this->assertSame('MyValue', $result);
        $this->assertSame(0, $queue->count());
        return $queue;
    }

    #[Depends('testTryDequeue_Success')]
    public function testTryDequeue_Failure(Queue $queue): void
    {
        $this->assertFalse($queue->tryDequeue($result));
        $this->assertNull($result);
    }

    public function testTryDequeue_FailureOverwritesResultWithNull(): void
    {
        $queue = new Queue();
        $result = 'previous';
        $this->assertFalse($queue->tryDequeue($result));
        $this->assertNull($result);
    }

    public function testTryDequeue_ReturnsItemsInFirstInFirstOutOrder(): void
    {
        $queue = new Queue(['A', 'B']);
        $this->assertTrue($queue->tryDequeue($result));
        $this->assertSame('A', $result);
        $queue->enqueue('C');
        $this->assertTrue($queue->tryDequeue($result));
        $this->assertSame('B', $result);
        $this->assertTrue($queue->tryDequeue($result));
        $this->assertSame('C', $result);
        $this->assertFalse($queue->tryDequeue($result));
        $this->assertNull($result);
        $this->assertSame(0, $queue->count());
    }

    public function testTryDequeue_SucceedsWithNullItem(): void
    {
        $queue = new Queue([null]);
        $result = 'previous';
        $this->assertTrue($queue->tryDequeue($result));
        $this->assertNull($result);
        $this->assertTrue($queue->isEmpty());
    }

    public function testTryPeek_Success(): void
    {
        $queue = new Queue();
        $queue->enqueue('MyValue');

        $this->assertTrue($queue->tryPeek($result));
        $this->assertSame('MyValue', $result);
        $this->assertSame(1, $queue->count());
    }

    public function testTryPeek_ReturnsHeadAfterDequeue(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $queue->dequeue();
        $this->assertTrue($queue->tryPeek($result));
        $this->assertSame('B', $result);
        $this->assertSame(2, $queue->count());
    }

    public function testTryPeek_Failure(): void
    {
        $queue = new Queue();
        $result = 'previous';
        $this->assertFalse($queue->tryPeek($result));
        $this->assertNull($result);
        $this->assertSame(0, $queue->count());
    }

    public function testToArrayReturnsItemsInFirstInFirstOutOrder(): void
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $queue->enqueue('C');

        $this->assertSame(['A', 'B', 'C'], $queue->toArray());
        $this->assertSame(3, $queue->count());
    }

    public function testToArrayIsReindexedAfterDequeue(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $queue->dequeue();
        $this->assertSame([0 => 'B', 1 => 'C'], $queue->toArray());
    }

    public function testToArrayIsIsolatedCopy(): void
    {
        $queue = new Queue(['A', 'B']);
        $array = $queue->toArray();
        $array[] = 'Z';
        $queue->enqueue('C');

        $this->assertSame(['A', 'B', 'Z'], $array);
        $this->assertSame(['A', 'B', 'C'], $queue->toArray());
    }

    public function testToArrayOnEmptyQueue(): void
    {
        $this->assertSame([], (new Queue())->toArray());
    }

    public function testClear(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $queue->dequeue();
        $queue->clear();

        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
        $this->assertSame([], $queue->toArray());
        $this->assertSame([], iterator_to_array($queue, false));
        $this->assertFalse($queue->contains('B'));
        $this->assertFalse($queue->tryPeek($result));
        $this->assertNull($result);
    }

    public function testClearOnEmptyQueue(): void
    {
        $queue = new Queue();
        $queue->clear();
        $this->assertTrue($queue->isEmpty());
    }

    public function testClearThenReuse(): void
    {
        $queue = new Queue(['A', 'B', 'C']);
        $queue->dequeue();
        $queue->clear();
        $queue->enqueue('X');
        $queue->enqueue('Y');

        $this->assertSame(2, $queue->count());
        $this->assertSame('X', $queue->peek());
        $this->assertSame(['X', 'Y'], $queue->toArray());
        $this->assertSame(['X', 'Y'], iterator_to_array($queue, false));
        $this->assertFalse($queue->contains('B'));
        $this->assertSame('X', $queue->dequeue());
        $this->assertSame('Y', $queue->dequeue());
        $this->assertTrue($queue->isEmpty());
    }

    public function testDequeueEverythingThenEnqueueAgain(): void
    {
        $queue = new Queue();
        $queue->enqueue('A');
        $queue->enqueue('B');
        $queue->dequeue();
        $queue->dequeue();
        $queue->enqueue('C');
        $queue->enqueue('D');

        $this->assertSame(2, $queue->count());
        $this->assertSame('C', $queue->peek());
        $this->assertSame(['C', 'D'], $queue->toArray());
        $this->assertSame(['C', 'D'], iterator_to_array($queue, false));
        $this->assertSame('C', $queue->dequeue());
        $this->assertSame('D', $queue->dequeue());
        $this->assertTrue($queue->isEmpty());
    }

    public function testConstructedQueueDequeueEverythingThenEnqueueAgain(): void
    {
        $queue = new Queue(['A', 'B']);
        $queue->dequeue();
        $queue->dequeue();
        $queue->enqueue('C');

        $this->assertSame(1, $queue->count());
        $this->assertSame('C', $queue->peek());
        $this->assertSame(['C'], $queue->toArray());
        $this->assertSame(['C'], iterator_to_array($queue, false));
        $this->assertSame('C', $queue->dequeue());
    }

    public function testManyInterleavedEnqueuesAndDequeues(): void
    {
        $queue = new Queue();
        foreach (range(1, 1000) as $i) {
            $queue->enqueue($i);
        }
        foreach (range(1, 990) as $i) {
            $this->assertSame($i, $queue->dequeue());
        }
        foreach (range(2001, 2005) as $i) {
            $queue->enqueue($i);
        }

        $expected = [991, 992, 993, 994, 995, 996, 997, 998, 999, 1000, 2001, 2002, 2003, 2004, 2005];
        $this->assertSame(15, $queue->count());
        $this->assertSame(991, $queue->peek());
        $this->assertSame($expected, $queue->toArray());
        $this->assertSame($expected, iterator_to_array($queue, false));
        $this->assertTrue($queue->contains(991));
        $this->assertTrue($queue->contains(2005));
        $this->assertFalse($queue->contains(990));
        $this->assertFalse($queue->contains(1));

        foreach ($expected as $item) {
            $this->assertSame($item, $queue->dequeue());
        }
        $this->assertTrue($queue->isEmpty());

        $queue->enqueue('again');
        $this->assertSame('again', $queue->peek());
        $this->assertSame(['again'], $queue->toArray());
        $this->assertSame(['again'], iterator_to_array($queue, false));
    }

    public function testAlternatingEnqueueAndDequeue(): void
    {
        $queue = new Queue();
        $model = [];
        for ($i = 0; $i < 500; ++$i) {
            $queue->enqueue($i);
            $queue->enqueue($i + 10000);
            $model[] = $i;
            $model[] = $i + 10000;
            $this->assertSame(array_shift($model), $queue->dequeue());
        }
        $this->assertSame(500, $queue->count());
        $this->assertSame($model, $queue->toArray());
        $this->assertSame($model, iterator_to_array($queue, false));
        $this->assertSame($model[0], $queue->peek());
    }
}
