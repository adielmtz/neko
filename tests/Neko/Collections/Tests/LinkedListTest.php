<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\Dictionary;
use Neko\Collections\LinkedList;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use function serialize;
use function unserialize;

final class LinkedListTest extends TestCase
{
    private LinkedList $list;

    public function setUp(): void
    {
        $this->list = new LinkedList();
        $this->list->add('A'); // 0
        $this->list->add('B'); // 1
        $this->list->add('C'); // 2
        $this->list->add('D'); // 3
        $this->list->add('E'); // 4
    }

    public function testConstructorWithArrayArgument(): LinkedList
    {
        $list = new LinkedList(['X', 'Y', 'Z']);
        $list->add('W');

        $this->assertSame(4, $list->count());
        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
        $this->assertSame('Z', $list->get(2));
        $this->assertSame('W', $list->get(3));
        return $list;
    }

    /**
     * @depends testConstructorWithArrayArgument
     */
    public function testConstructorWithIterableArgument(LinkedList $argument): void
    {
        $list = new LinkedList($argument);
        $list->add('A');

        $this->assertSame(5, $list->count());
        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
        $this->assertSame('Z', $list->get(2));
        $this->assertSame('W', $list->get(3));
        $this->assertSame('A', $list->get(4));
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary();
        $argument->add('k', 'v');
        $argument->add('a', 'b');

        $list = new LinkedList($argument);
        $this->assertSame(2, $list->count());
        $this->assertSame('v', $list->get(0));
        $this->assertSame('b', $list->get(1));
    }

    public function testSerialize(): string
    {
        $list = new LinkedList();
        $list->add('X');
        $list->add(504);
        $list->add([]);

        $serialized = serialize($list);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    /**
     * @depends testSerialize
     */
    public function testUnserialize(string $serialized): LinkedList
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(LinkedList::class, $restored);
        return $restored;
    }

    /**
     * @depends testUnserialize
     */
    public function testUnserializedLinkedListKeepsOrderOfElements(LinkedList $restored): void
    {
        $this->assertSame(3, $restored->count());
        $this->assertSame('X', $restored->get(0));
        $this->assertSame(504, $restored->get(1));
        $this->assertIsArray($restored->get(2));
    }

    public function testSerializeEmptyLinkedList(): string
    {
        $empty = new LinkedList();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    /**
     * @depends testSerializeEmptyLinkedList
     */
    public function testUnserializeEmptyLinkedList(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(LinkedList::class, $restored);
        $this->assertSame(0, $restored->count());
    }

    public function testIterator(): void
    {
        $items = ['A', 'B', 'C', 'D', 'E'];
        $index = 0;

        foreach ($this->list as $i => $value) {
            $this->assertSame($index, $i);
            $this->assertSame($items[$index], $value);
            $index++;
        }
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);

        foreach ($this->list as $value) {
            if ($value === 'B') {
                $this->list->add('Z');
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
        $this->assertTrue($this->list->containsAll(new LinkedList(['B', 'C', 'D'])));
        $this->assertFalse($this->list->containsAll(new LinkedList(['X', 'Y', 'Z'])));
    }

    public function testAddLast(): void
    {
        $this->list->addLast('F');
        $this->list->addLast('G');
        $this->list->addLast('H');
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

    public function testInsertOnEmptyList(): void
    {
        $list = new LinkedList();
        $list->insert(0, 'ABC');
        $this->assertSame('ABC', $list->get(0));
    }

    public function testRemoveReturnsTrue(): void
    {
        $this->assertTrue($this->list->remove('A'));
        $this->assertTrue($this->list->remove('D'));
        $this->assertTrue($this->list->remove('E'));
    }

    public function testRemoveReturnsFalse(): void
    {
        $this->assertFalse($this->list->remove('X'));
    }

    public function testInsertOnEmptyListThrowsWhenTheIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $list = new LinkedList();
        $list->insert(10, 'ABC');
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

    public function testRemoveFirst(): void
    {
        $list = new LinkedList();
        $originalHead = $this->list->getFirst();
        $this->list->removeFirst();

        $this->assertNotSame($originalHead, $this->list->getFirst());
        $this->assertSame('B', $this->list->get(0));
    }

    public function testRemoveFirstOnEmptyListMustBeNullSafe(): void
    {
        $this->list->clear();
        $this->list->removeFirst();
        $this->assertSame(0, $this->list->count());
    }

    public function testRemoveLast(): void
    {
        $originalTail = $this->list->getLast();
        $this->list->removeLast();

        $this->assertNotSame($originalTail, $this->list->getLast());
        $this->assertSame('D', $this->list->get($this->list->count() - 1));
    }

    public function testRemoveLastOnEmptyListMustBeNullSafe(): void
    {
        $this->list->clear();
        $this->list->removeLast();
        $this->assertSame(0, $this->list->count());
    }

    public function testIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(2, $list->indexOf('C'));
    }

    public function testIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->indexOf('Z'));
    }

    public function testLastIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(3, $list->lastIndexOf('C'));
    }

    public function testLastIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $this->list->lastIndexOf('Z'));
    }
}
