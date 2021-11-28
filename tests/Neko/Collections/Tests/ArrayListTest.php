<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ArrayList;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use function ord;
use function range;

final class ArrayListTest extends TestCase
{
    private ArrayList $list;

    public function setUp(): void
    {
        $this->list = new ArrayList();
        $this->list->add('A'); // 0
        $this->list->add('B'); // 1
        $this->list->add('C'); // 2
        $this->list->add('D'); // 3
        $this->list->add('E'); // 4
    }

    public function testClear(): void
    {
        $this->list->clear();
        $this->assertTrue($this->list->isEmpty());
        $this->assertSame(0, $this->list->count());
    }

    public function testContains(): void
    {
        $this->assertTrue($this->list->contains('C'));
        $this->assertFalse($this->list->contains('X'));
    }

    public function testAdd(): void
    {
        $this->list->add('F');
        $this->list->add('G');
        $this->list->add('H');
        $this->assertFalse($this->list->isEmpty());
        $this->assertSame(8, $this->list->count());
        $this->assertSame('F', $this->list->get(5));
        $this->assertSame('G', $this->list->get(6));
        $this->assertSame('H', $this->list->get(7));
    }

    public function testGet(): void
    {
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('E', $this->list->get(4));
    }

    public function testGetFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->get(100);
    }

    public function testSet(): void
    {
        $copy = clone $this->list;

        // Replace a value
        $copy->set(1, 'X');
        $this->assertNotSame($this->list->get(1), $copy->get(1));
        $this->assertSame('X', $copy->get(1));
        $this->assertSame(5, $copy->count());
    }

    public function testSetFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->set(100, null);
    }

    public function testInsertFirst(): void
    {
        $this->list->insert(0, 'Elite Miko');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('Elite Miko', $this->list->get(0));
        $this->assertSame('A', $this->list->get(1));
        $this->assertSame('B', $this->list->get(2));
        $this->assertSame('C', $this->list->get(3));
        $this->assertSame('D', $this->list->get(4));
        $this->assertSame('E', $this->list->get(5));
    }

    public function testInsertFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insert(100, null);
    }

    public function testInsertBetween(): void
    {
        $this->list->insert(3, 'Shishiron');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('Shishiron', $this->list->get(3));
        $this->assertSame('D', $this->list->get(4));
        $this->assertSame('E', $this->list->get(5));
    }

    public function testInsertLast(): void
    {
        $this->list->insert($this->list->count(), 'Gura');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('D', $this->list->get(3));
        $this->assertSame('E', $this->list->get(4));
        $this->assertSame('Gura', $this->list->get(5));
    }

    public function testInsertRange_First(): void
    {
        // This test also serves as test for ArrayList::addRange() method.
        $this->list->insertRange(0, ['X', 'Y', 'Z']); // [X, Y, Z, A, B, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame('X', $this->list->get(0));
        $this->assertSame('Y', $this->list->get(1));
        $this->assertSame('Z', $this->list->get(2));
        $this->assertSame('A', $this->list->get(3));
        $this->assertSame('B', $this->list->get(4));
        $this->assertSame('C', $this->list->get(5));
        $this->assertSame('D', $this->list->get(6));
        $this->assertSame('E', $this->list->get(7));
    }

    public function testInsertRange_FailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insertRange(100, []);
    }

    public function testInsertRange_Between(): void
    {
        $this->list->insertRange(2, ['X', 'Y', 'Z']); // [A, B, X, Y, Z, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('X', $this->list->get(2));
        $this->assertSame('Y', $this->list->get(3));
        $this->assertSame('Z', $this->list->get(4));
        $this->assertSame('C', $this->list->get(5));
        $this->assertSame('D', $this->list->get(6));
        $this->assertSame('E', $this->list->get(7));
    }

    public function testInsertRange_Last(): void
    {
        $this->list->insertRange(5, ['X', 'Y', 'Z']); // [A, B, C, D, E, X, Y, Z]
        $this->assertSame(8, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('D', $this->list->get(3));
        $this->assertSame('E', $this->list->get(4));
        $this->assertSame('X', $this->list->get(5));
        $this->assertSame('Y', $this->list->get(6));
        $this->assertSame('Z', $this->list->get(7));
    }

    public function testRemoveAt_(): void
    {
        $this->list->removeAt(2); // [A, B, D, E]
        $this->assertSame(4, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('D', $this->list->get(2));
        $this->assertSame('E', $this->list->get(3));
    }

    public function testRemoveAt_FailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeAt(100);
    }

    public function testRemoveRange_First(): void
    {
        $this->list->removeRange(0, 3); // [D, E]
        $this->assertSame(2, $this->list->count());
        $this->assertSame('D', $this->list->get(0));
        $this->assertSame('E', $this->list->get(1));
    }

    public function testRemoveRange_FailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeRange(100, 100);
    }

    public function testRemoveRange_Between(): void
    {
        // Remove B, C, D
        $this->list->removeRange(1, 3); // [A, E]
        $this->assertSame(2, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('E', $this->list->get(1));
    }

    public function testRemoveRange_Last(): void
    {
        // Remove D, E
        $this->list->removeRange(3, 2); // [A, B, C]
        $this->assertSame(3, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));

        $this->expectException(OutOfBoundsException::class);
        $this->list->get(3);
    }

    public function testRemoveRange_WithoutCountParameter(): void
    {
        // Remove everything after B
        $this->list->removeRange(2); // [A, B]
        $this->assertSame(2, $this->list->count());
    }

    public function testIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(2, $list->indexOf('C'));
    }

    public function testIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->indexOf('Z'));
    }

    public function testLastIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(3, $list->lastIndexOf('C'));
    }

    public function testLastIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $this->list->lastIndexOf('Z'));
    }

    public function testBinarySearch_FindsAValue(): void
    {
        $this->assertSame(0, $this->list->binarySearch('A'));
        $this->assertSame(1, $this->list->binarySearch('B'));
        $this->assertSame(2, $this->list->binarySearch('C'));
        $this->assertSame(3, $this->list->binarySearch('D'));
        $this->assertSame(4, $this->list->binarySearch('E'));
    }

    public function testBinarySearch_DidNotFindAValue(): void
    {
        $this->assertSame(-1, $this->list->binarySearch('X'));
        $this->assertSame(-1, $this->list->binarySearch('Y'));
        $this->assertSame(-1, $this->list->binarySearch('Z'));
    }

    public function testReverse(): void
    {
        $this->list->reverse();
        $this->assertSame(5, $this->list->count());
        $this->assertSame('E', $this->list->get(0));
        $this->assertSame('D', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('B', $this->list->get(3));
        $this->assertSame('A', $this->list->get(4));
    }

    public function testSlice(): void
    {
        $slice = $this->list->slice(1, 3);
        $this->assertSame(3, $slice->count());
        $this->assertSame('B', $slice->get(0));
        $this->assertSame('C', $slice->get(1));
        $this->assertSame('D', $slice->get(2));
    }

    public function testFilter(): void
    {
        $list = new ArrayList(range(1, 10));
        $filtered = $list->filter(fn($i) => $i % 2 === 0);
        $this->assertSame(5, $filtered->count());
        $this->assertSame(2, $filtered->get(0));
        $this->assertSame(4, $filtered->get(1));
        $this->assertSame(6, $filtered->get(2));
        $this->assertSame(8, $filtered->get(3));
        $this->assertSame(10, $filtered->get(4));
    }

    public function testMap(): void
    {
        $list = $this->list->map(fn($c) => ord($c));
        $this->assertSame(5, $list->count());
        $this->assertSame(ord('A'), $list->get(0));
        $this->assertSame(ord('B'), $list->get(1));
        $this->assertSame(ord('C'), $list->get(2));
        $this->assertSame(ord('D'), $list->get(3));
        $this->assertSame(ord('E'), $list->get(4));
    }

    public function testTrueForAll_ReturnsTrue(): void
    {
        $this->assertTrue($this->list->trueForAll(fn($c) => $c >= 'A' && $c <= 'Z'));
    }

    public function testTrueForAll_ReturnsFalse(): void
    {
        $this->assertFalse($this->list->trueForAll(fn($c) => $c === 'A'));
    }

    public function testTrueForAny_ReturnsTrue(): void
    {
        $this->assertTrue($this->list->trueForAny(fn($c) => $c === 'D'));
    }

    public function testTrueForAny_ReturnsFalse(): void
    {
        $this->assertFalse($this->list->trueForAny(fn($c) => $c >= '0' && $c <= '9'));
    }
}
