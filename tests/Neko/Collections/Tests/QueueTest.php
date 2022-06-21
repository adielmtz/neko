<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ArrayList;
use Neko\Collections\Dictionary;
use Neko\Collections\Queue;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class QueueTest extends TestCase
{
    public function testConstructorWithArrayArgument(): void
    {
        $queue = new Queue(['X', 'Y', 'Z']);
        $this->assertSame(3, $queue->count());
        $this->assertSame('X', $queue->dequeue());
        $this->assertSame('Y', $queue->dequeue());
        $this->assertSame('Z', $queue->dequeue());
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

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary(['A' => 'a', 'B' => 'b', 'C' => 'c']);
        $queue = new Queue($argument);

        $this->assertSame(3, $queue->count());
        $this->assertSame('a', $queue->dequeue());
        $this->assertSame('b', $queue->dequeue());
        $this->assertSame('c', $queue->dequeue());
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
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

    public function testEmpty(): Queue
    {
        $queue = new Queue();
        $this->assertTrue($queue->isEmpty());
        $this->assertSame(0, $queue->count());
        return $queue;
    }

    /**
     * @depends testEmpty
     */
    public function testEnqueue(Queue $queue): Queue
    {
        $value = 'Pekora';
        $queue->enqueue($value);
        $this->assertSame($value, $queue->peek());
        $this->assertSame(1, $queue->count());
        $this->assertFalse($queue->isEmpty());
        return $queue;
    }

    /**
     * @depends testEnqueue
     */
    public function testContains(Queue $queue): Queue
    {
        $this->assertTrue($queue->contains('Pekora'));
        $this->assertFalse($queue->contains('Carrots'));
        return $queue;
    }

    /**
     * @depends testContains
     */
    public function testDequeue(Queue $queue): void
    {
        $this->assertSame('Pekora', $queue->dequeue());
        $this->assertSame(0, $queue->count());
        $this->assertTrue($queue->isEmpty());
    }

    public function testDequeueThrowsExceptionIfIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        $queue = new Queue();
        $queue->dequeue();
    }

    public function testTryDequeue_Success(): Queue
    {
        $queue = new Queue();
        $queue->enqueue('Watame');

        $this->assertTrue($queue->tryDequeue($result));
        $this->assertSame('Watame', $result);
        $this->assertSame(0, $queue->count());
        return $queue;
    }

    /**
     * @depends testTryDequeue_Success
     */
    public function testTryPop_Failure(Queue $queue): void
    {
        $this->assertFalse($queue->tryDequeue($result));
        $this->assertNull($result);
    }

    public function testTryPeek_Success(): void
    {
        $queue = new Queue();
        $queue->enqueue('Botan');

        $this->assertTrue($queue->tryPeek($result));
        $this->assertSame('Botan', $result);
        $this->assertSame(1, $queue->count());
    }
}
