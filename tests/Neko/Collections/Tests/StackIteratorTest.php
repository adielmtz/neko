<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\Stack;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class StackIteratorTest extends TestCase
{
    public function testIteratorThrowsExceptionWhenAccessingKey(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->push('abc');

        foreach ($stack as $key => $value) {
            $this->assertSame('abc', $value);
        }
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stack = new Stack();
        $stack->push('A');
        $stack->push('B');
        $stack->push('C');

        foreach ($stack as $char) {
            if ($char === 'C') {
                $stack->push('D');
            }
        }
    }
}
