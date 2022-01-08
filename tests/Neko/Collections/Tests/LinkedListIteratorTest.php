<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\LinkedList;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class LinkedListIteratorTest extends TestCase
{
    public function testIterator(): void
    {
        $items = ['A', 'B', 'C', 'X', 'Y', 'Z'];
        $list = new LinkedList($items);

        foreach ($list as $i => $actual) {
            $expected = $items[$i];
            $this->assertSame($expected, $actual);
        }
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $list = new LinkedList();
        $list->add('A');
        $list->add('B');
        $list->add('C');

        foreach ($list as $char) {
            if ($char === 'C') {
                $list->add('D');
            }
        }
    }
}
