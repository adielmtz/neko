<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ArrayList;
use Neko\Collections\Dictionary;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use function implode;
use function ord;
use function range;
use function serialize;
use function str_shuffle;
use function str_split;
use function unserialize;

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

    public function testArrayAccessOffsetSet(): void
    {
        $size = $this->list->count();
        $this->list[] = 'X';
        $this->assertSame('X', $this->list[$size]);
    }

    public function testArrayAccessOffsetExists(): void
    {
        $this->assertTrue(isset($this->list[0]));
        $this->assertFalse(isset($this->list[100]));
        $this->assertFalse(isset($this->list['key']));
    }

    public function testConstructorWithArrayArgument(): ArrayList
    {
        $list = new ArrayList(['X', 'Y', 'Z']);
        $list->add('W');

        $this->assertSame(4, $list->count());
        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
        $this->assertSame('Z', $list->get(2));
        $this->assertSame('W', $list->get(3));
        return $list;
    }

    #[Depends('testConstructorWithArrayArgument')]
    public function testConstructorWithIterableArgument(ArrayList $argument): void
    {
        $list = new ArrayList($argument);
        $list->add('A');

        $this->assertSame(5, $list->count());
        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
        $this->assertSame('Z', $list->get(2));
        $this->assertSame('W', $list->get(3));
        $this->assertSame('A', $list->get(4));
    }

    public function testSerialize(): string
    {
        $list = new ArrayList();
        $list->add('X');
        $list->add(504);
        $list->add([]);

        $serialized = serialize($list);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerialize')]
    public function testUnserialize(string $serialized): ArrayList
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(ArrayList::class, $restored);
        return $restored;
    }

    #[Depends('testUnserialize')]
    public function testUnserializedArrayListKeepsOrderOfElements(ArrayList $restored): void
    {
        $this->assertSame(3, $restored->count());
        $this->assertSame('X', $restored->get(0));
        $this->assertSame(504, $restored->get(1));
        $this->assertIsArray($restored->get(2));
    }

    public function testSerializeEmptyArrayList(): string
    {
        $empty = new ArrayList();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerializeEmptyArrayList')]
    public function testUnserializeEmptyArrayList(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(ArrayList::class, $restored);
        $this->assertSame(0, $restored->count());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary();
        $argument->add('k', 'v');
        $argument->add('a', 'b');

        $list = new ArrayList($argument);
        $this->assertSame(2, $list->count());
        $this->assertSame('v', $list->get(0));
        $this->assertSame('b', $list->get(1));
    }

    public function testIteratorThrowsExceptionIfTheListIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);

        foreach ($this->list as $char) {
            if ($char === 'C') {
                $this->list->add('D');
            }
        }
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

    public function testContainsAllWithArray(): void
    {
        $this->assertTrue($this->list->containsAll(['B', 'C', 'D']));
        $this->assertFalse($this->list->containsAll(['X', 'Y', 'Z']));
    }

    public function testContainsAllWithCollection(): void
    {
        $this->assertTrue($this->list->containsAll(new ArrayList(['B', 'C', 'D'])));
        $this->assertFalse($this->list->containsAll(new ArrayList(['X', 'Y', 'Z'])));
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
        $this->list->insertAll(0, ['X', 'Y', 'Z']); // [X, Y, Z, A, B, C, D, E]
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
        $this->list->insertAll(100, []);
    }

    public function testInsertRange_Between(): void
    {
        $this->list->insertAll(2, ['X', 'Y', 'Z']); // [A, B, X, Y, Z, C, D, E]
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
        $this->list->insertAll(5, ['X', 'Y', 'Z']); // [A, B, C, D, E, X, Y, Z]
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

    public function testInsertRange_UsingAnAssociativeArray(): void
    {
        $this->list->insertAll(1, ['foo' => 'X', 'bar' => 'Y', 'baz' => 'Z']);
        $this->assertSame(8, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('X', $this->list->get(1));
        $this->assertSame('Y', $this->list->get(2));
        $this->assertSame('Z', $this->list->get(3));
        $this->assertSame('B', $this->list->get(4));
        $this->assertSame('C', $this->list->get(5));
        $this->assertSame('D', $this->list->get(6));
        $this->assertSame('E', $this->list->get(7));
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

    public function testRemoveRange_ReturnsNumberOfElementsRemoved(): void
    {
        $removed = $this->list->removeRange(0, 3);
        $this->assertSame(3, $removed);
    }

    public function testRemoveRange_RemovesAllValuesIfCountIsLargerThenTheSizeOfTheList(): void
    {
        $removed = $this->list->removeRange(0, 10000);
        $this->assertSame(5, $removed);
        $this->assertSame(0, $this->list->count());
    }

    public function testRemoveRange_RemovesRemainingValuesIfCountIsLargerThanTheSizeOfTheList(): void
    {
        $removed = $this->list->removeRange(4, 1000);
        $this->assertSame(1, $removed);
        $this->assertSame(4, $this->list->count());
    }

    public function testRemoveIfTrue(): void
    {
        $count = $this->list->removeIf(fn($c) => ord($c) % 2 === 0);
        $this->assertSame(2, $count);
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

    public function testSliceFromBeginningOfList(): void
    {
        $slice = $this->list->slice(0, 3);
        $this->assertSame(3, $slice->count());
        $this->assertSame('A', $slice->get(0));
        $this->assertSame('B', $slice->get(1));
        $this->assertSame('C', $slice->get(2));
    }

    public function testSliceFromMiddleOfList(): void
    {
        $slice = $this->list->slice(2, 2);
        $this->assertSame(2, $slice->count());
        $this->assertSame('C', $slice->get(0));
        $this->assertSame('D', $slice->get(1));
    }

    public function testSliceReturnsAnEmptyList(): void
    {
        $slice = $this->list->slice(0, 0);
        $this->assertTrue($slice->isEmpty());
    }

    public function testSliceStopsWhenCountArgumentIsLargerThanTheSizeOfTheList(): void
    {
        $slice = $this->list->slice(3, 1000);
        $this->assertSame(2, $slice->count());
        $this->assertSame('D', $slice->get(0));
        $this->assertSame('E', $slice->get(1));
    }

    public function testSliceReturnsRestOfTheList(): void
    {
        $slice = $this->list->slice(0, 100);
        $this->assertSame(5, $slice->count());
    }

    public function testFilter(): void
    {
        $list = new ArrayList(range(1, 10));
        $filter = $list->filter(fn($i) => $i % 2 === 0);
        $this->assertSame(5, $filter->count());
        $this->assertSame(2, $filter->get(0));
        $this->assertSame(4, $filter->get(1));
        $this->assertSame(6, $filter->get(2));
        $this->assertSame(8, $filter->get(3));
        $this->assertSame(10, $filter->get(4));
    }

    public function testFilterReturnsEmptyList(): void
    {
        $filter = $this->list->filter(fn() => false);
        $this->assertTrue($filter->isEmpty());
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

    public function testForEach(): void
    {
        $expected_str = implode('', $this->list->toArray());
        $actual_str = '';

        $this->list->forEach(function (string $c) use (&$actual_str): void {
            $actual_str .= $c;
        });

        $this->assertSame($expected_str, $actual_str);
    }

    public function testForEachThrowsExceptionIfCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->list->forEach(fn($x) => $this->list->add('Not possible!'));
    }

    public function testSortWithoutComparator(): void
    {
        $shuffled = str_shuffle('BACEDGF');
        $characters = str_split($shuffled);

        $list = new ArrayList($characters);
        $list->sort();

        $this->assertSame('A', $list->get(0));
        $this->assertSame('B', $list->get(1));
        $this->assertSame('C', $list->get(2));
        $this->assertSame('D', $list->get(3));
        $this->assertSame('E', $list->get(4));
        $this->assertSame('F', $list->get(5));
        $this->assertSame('G', $list->get(6));
    }

    public function testSortWithComparator(): void
    {
        $shuffled = str_shuffle('BACEDGF');
        $characters = str_split($shuffled);

        $list = new ArrayList($characters);
        $list->sort(fn($a, $b) => $b <=> $a); // Sort in descending order

        $this->assertSame('G', $list->get(0));
        $this->assertSame('F', $list->get(1));
        $this->assertSame('E', $list->get(2));
        $this->assertSame('D', $list->get(3));
        $this->assertSame('C', $list->get(4));
        $this->assertSame('B', $list->get(5));
        $this->assertSame('A', $list->get(6));
    }

    public function testSortRange_WithoutComparator(): void
    {
        $list = new ArrayList(['a', 'b', 'c', 3, 2, 1, 'x', 'y', 'z']);
        $list->sortRange(3, 3);

        $this->assertSame('a', $list->get(0));
        $this->assertSame('b', $list->get(1));
        $this->assertSame('c', $list->get(2));
        $this->assertSame(1, $list->get(3));
        $this->assertSame(2, $list->get(4));
        $this->assertSame(3, $list->get(5));
        $this->assertSame('x', $list->get(6));
        $this->assertSame('y', $list->get(7));
        $this->assertSame('z', $list->get(8));
    }

    public function testSortRange_WithComparator(): void
    {
        $list = new ArrayList(['a', 'b', 'c', 3, 2, 1, 'x', 'y', 'z']);
        $list->sortRange(0, 3, fn($a, $b) => $b <=> $a); // descending order
        $list->sortRange(6, 3, fn($a, $b) => $b <=> $a);

        $this->assertSame('c', $list->get(0));
        $this->assertSame('b', $list->get(1));
        $this->assertSame('a', $list->get(2));
        $this->assertSame(3, $list->get(3));
        $this->assertSame(2, $list->get(4));
        $this->assertSame(1, $list->get(5));
        $this->assertSame('z', $list->get(6));
        $this->assertSame('y', $list->get(7));
        $this->assertSame('x', $list->get(8));
    }

    public function testSortRange_SortStart(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(0, 3);

        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
        $this->assertSame('Z', $list->get(2));
        $this->assertSame('B', $list->get(3));
        $this->assertSame('C', $list->get(4));
        $this->assertSame('A', $list->get(5));
        $this->assertSame('D', $list->get(6));
    }

    public function testSortRange_SortBetween(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(2, 3);

        $this->assertSame('Z', $list->get(0));
        $this->assertSame('X', $list->get(1));
        $this->assertSame('B', $list->get(2));
        $this->assertSame('C', $list->get(3));
        $this->assertSame('Y', $list->get(4));
        $this->assertSame('A', $list->get(5));
        $this->assertSame('D', $list->get(6));
    }

    public function testSortRange_SortLast(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(4, 3);

        $this->assertSame('Z', $list->get(0));
        $this->assertSame('X', $list->get(1));
        $this->assertSame('Y', $list->get(2));
        $this->assertSame('B', $list->get(3));
        $this->assertSame('A', $list->get(4));
        $this->assertSame('C', $list->get(5));
        $this->assertSame('D', $list->get(6));
    }

    public function testSortRange_PassingListCount(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(0, $list->count());

        $this->assertSame('A', $list->get(0));
        $this->assertSame('B', $list->get(1));
        $this->assertSame('C', $list->get(2));
        $this->assertSame('D', $list->get(3));
        $this->assertSame('X', $list->get(4));
        $this->assertSame('Y', $list->get(5));
        $this->assertSame('Z', $list->get(6));
    }

    public function testTrueForAll_ReturnsTrue(): void
    {
        $this->assertTrue($this->list->all(fn($c) => $c >= 'A' && $c <= 'Z'));
    }

    public function testTrueForAll_ReturnsFalse(): void
    {
        $this->assertFalse($this->list->all(fn($c) => $c === 'A'));
    }

    public function testTrueForAny_ReturnsTrue(): void
    {
        $this->assertTrue($this->list->any(fn($c) => $c === 'D'));
    }

    public function testTrueForAny_ReturnsFalse(): void
    {
        $this->assertFalse($this->list->any(fn($c) => $c >= '0' && $c <= '9'));
    }
}
