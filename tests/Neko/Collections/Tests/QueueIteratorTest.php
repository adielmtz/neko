<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\Queue;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class QueueIteratorTest extends TestCase
{
    public function testIteratorThrowsExceptionWhenAccessingKey(): void
    {
        $this->expectException(InvalidOperationException::class);
        $queue = new Queue();
        $queue->enqueue('abc');

        foreach ($queue as $key => $value) {
            $this->assertSame('abc', $value);
        }
    }
}
