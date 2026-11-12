<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ConcurrentMutationException;
use Neko\Collections\Dictionary;
use Neko\Collections\LinkedList;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use TypeError;
use function iterator_to_array;
use function range;
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

    #region Constructor
    public function testConstructorWithoutArgumentCreatesAnEmptyList(): void
    {
        $list = new LinkedList();
        $this->assertTrue($list->isEmpty());
        $this->assertSame(0, $list->count());
        $this->assertSame([], $list->toArray());
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

    #[Depends('testConstructorWithArrayArgument')]
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

        // The source list is not affected
        $this->assertSame(4, $argument->count());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary(['k' => 'v']);
        $argument->set('a', 'b');

        $list = new LinkedList($argument);
        $this->assertSame(2, $list->count());
        $this->assertSame('v', $list->get(0));
        $this->assertSame('b', $list->get(1));
    }

    public function testConstructorWithAssociativeArrayDiscardsKeys(): void
    {
        $list = new LinkedList(['foo' => 'X', 'bar' => 'Y']);
        $this->assertSame(['X', 'Y'], $list->toArray());
    }
    #endregion

    #region Serialization
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

    #[Depends('testSerialize')]
    public function testUnserialize(string $serialized): LinkedList
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(LinkedList::class, $restored);
        return $restored;
    }

    #[Depends('testUnserialize')]
    public function testUnserializedLinkedListKeepsOrderOfElements(LinkedList $restored): void
    {
        $this->assertSame(3, $restored->count());
        $this->assertSame('X', $restored->get(0));
        $this->assertSame(504, $restored->get(1));
        $this->assertSame([], $restored->get(2));
    }

    public function testSerializeEmptyLinkedList(): string
    {
        $empty = new LinkedList();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerializeEmptyLinkedList')]
    public function testUnserializeEmptyLinkedList(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(LinkedList::class, $restored);
        $this->assertSame(0, $restored->count());
        $this->assertTrue($restored->isEmpty());

        $restored->add('A');
        $this->assertSame(['A'], $restored->toArray());
    }

    public function testSerializeProducesAPlainArray(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->__serialize());
        $this->assertStringNotContainsString('LinkedListNode', serialize($this->list));
    }

    public function testSerializeRoundTripPreservesItems(): void
    {
        $restored = unserialize(serialize($this->list));
        $this->assertInstanceOf(LinkedList::class, $restored);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $restored->toArray());
        $this->assertSame('A', $restored->getFirst());
        $this->assertSame('E', $restored->getLast());
    }

    public function testSerializeRoundTripOfLargeListDoesNotOverflowTheStack(): void
    {
        $size = 300000;
        $list = new LinkedList(range(0, $size - 1));

        $restored = unserialize(serialize($list));
        $this->assertInstanceOf(LinkedList::class, $restored);
        $this->assertSame($size, $restored->count());
        $this->assertSame(0, $restored->getFirst());
        $this->assertSame($size - 1, $restored->getLast());
        $this->assertSame(150000, $restored->get(150000));

        // The restored list is fully functional
        $restored->addFirst(-1);
        $this->assertSame($size - 1, $restored->removeLast());
        $this->assertSame($size, $restored->count());
        $this->assertSame(-1, $restored->getFirst());
        $this->assertSame($size - 2, $restored->getLast());
    }
    #endregion

    #region Clone
    public function testCloneHasTheSameItems(): void
    {
        $copy = clone $this->list;
        $this->assertNotSame($this->list, $copy);
        $this->assertSame($this->list->toArray(), $copy->toArray());
        $this->assertSame(5, $copy->count());
    }

    public function testModifyingTheCloneDoesNotAffectTheOriginal(): void
    {
        $copy = clone $this->list;
        $copy->set(0, 'X');
        $copy->addFirst('Y');
        $copy->addLast('Z');
        $copy->removeAt(3);
        $copy->insert(2, 'W');

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame(['Y', 'X', 'W', 'B', 'D', 'E', 'Z'], $copy->toArray());
    }

    public function testModifyingTheOriginalDoesNotAffectTheClone(): void
    {
        $copy = clone $this->list;
        $this->list->set(0, 'X');
        $this->list->removeLast();
        $this->list->addFirst('Y');
        $this->list->reverse();

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $copy->toArray());
        $this->assertSame(['D', 'C', 'B', 'X', 'Y'], $this->list->toArray());
    }

    public function testClearingTheCloneDoesNotAffectTheOriginal(): void
    {
        $copy = clone $this->list;
        $copy->clear();

        $this->assertTrue($copy->isEmpty());
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testCloneEmptyList(): void
    {
        $copy = clone new LinkedList();
        $this->assertTrue($copy->isEmpty());

        $copy->add('A');
        $this->assertSame(['A'], $copy->toArray());
    }
    #endregion

    #region Iteration
    public function testIterator(): void
    {
        $items = ['A', 'B', 'C', 'D', 'E'];
        $index = 0;

        foreach ($this->list as $i => $value) {
            $this->assertSame($index, $i);
            $this->assertSame($items[$index], $value);
            $index++;
        }

        $this->assertSame(5, $index);
    }

    public function testIteratorOnEmptyList(): void
    {
        $this->assertSame([], iterator_to_array(new LinkedList()));
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $value) {
            if ($value === 'B') {
                $this->list->add('Z');
            }
        }
    }

    public function testIteratorThrowsExceptionIfTheCurrentItemIsRemoved(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $value) {
            if ($value === 'C') {
                $this->list->remove('C');
            }
        }
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModifiedOnTheLastItem(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $value) {
            if ($value === 'E') {
                $this->list->removeFirst();
            }
        }
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsCleared(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $value) {
            if ($value === 'A') {
                $this->list->clear();
            }
        }
    }
    #endregion

    #region Collection
    public function testIsEmpty(): void
    {
        $this->assertFalse($this->list->isEmpty());
        $this->assertTrue((new LinkedList())->isEmpty());
    }

    public function testCount(): void
    {
        $this->assertSame(5, $this->list->count());
        $this->assertCount(5, $this->list);
    }

    public function testClear(): void
    {
        $this->list->clear();
        $this->assertTrue($this->list->isEmpty());
        $this->assertSame(0, $this->list->count());
        $this->assertSame([], $this->list->toArray());
        $this->assertSame([], iterator_to_array($this->list));
    }

    public function testClearOnEmptyList(): void
    {
        $list = new LinkedList();
        $list->clear();
        $this->assertTrue($list->isEmpty());
    }

    public function testListIsUsableAfterClear(): void
    {
        $this->list->clear();
        $this->list->add('X');
        $this->list->addFirst('W');
        $this->list->addLast('Y');

        $this->assertSame(['W', 'X', 'Y'], $this->list->toArray());
        $this->assertSame('W', $this->list->getFirst());
        $this->assertSame('Y', $this->list->getLast());
    }

    public function testContains(): void
    {
        $this->assertTrue($this->list->contains('A'));
        $this->assertTrue($this->list->contains('C'));
        $this->assertTrue($this->list->contains('E'));
        $this->assertFalse($this->list->contains('X'));
        $this->assertFalse((new LinkedList())->contains('A'));
    }

    public function testContainsUsesStrictComparison(): void
    {
        $list = new LinkedList([1, '2', null]);
        $this->assertTrue($list->contains(1));
        $this->assertFalse($list->contains('1'));
        $this->assertFalse($list->contains(2));
        $this->assertTrue($list->contains(null));
        $this->assertFalse($list->contains(false));
    }

    public function testContainsAllWithArray(): void
    {
        $this->assertTrue($this->list->containsAll(['B', 'C', 'D']));
        $this->assertFalse($this->list->containsAll(['X', 'Y', 'Z']));
        $this->assertFalse($this->list->containsAll(['A', 'X']));
        $this->assertTrue($this->list->containsAll([]));
    }

    public function testContainsAllWithCollection(): void
    {
        $this->assertTrue($this->list->containsAll(new LinkedList(['B', 'C', 'D'])));
        $this->assertFalse($this->list->containsAll(new LinkedList(['X', 'Y', 'Z'])));
    }

    public function testAdd(): void
    {
        $this->list->add('F');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('F', $this->list->getLast());
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'F'], $this->list->toArray());
    }

    public function testAddOnEmptyList(): void
    {
        $list = new LinkedList();
        $list->add('A');
        $this->assertSame(1, $list->count());
        $this->assertSame('A', $list->getFirst());
        $this->assertSame('A', $list->getLast());
    }

    public function testAddAll(): void
    {
        $this->list->addAll(['X', 'Y']);
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X', 'Y'], $this->list->toArray());
    }

    public function testAddAllOnEmptyList(): void
    {
        $list = new LinkedList();
        $list->addAll(new LinkedList(['X', 'Y']));
        $this->assertSame(['X', 'Y'], $list->toArray());
    }

    public function testAddAllWithEmptyIterable(): void
    {
        $this->list->addAll([]);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testAddAllWithItself(): void
    {
        $this->list->addAll($this->list);
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testRemoveReturnsTrue(): void
    {
        $this->assertTrue($this->list->remove('A'));
        $this->assertTrue($this->list->remove('D'));
        $this->assertTrue($this->list->remove('E'));
        $this->assertSame(['B', 'C'], $this->list->toArray());
        $this->assertSame('B', $this->list->getFirst());
        $this->assertSame('C', $this->list->getLast());
    }

    public function testRemoveReturnsFalse(): void
    {
        $this->assertFalse($this->list->remove('X'));
        $this->assertSame(5, $this->list->count());
        $this->assertFalse((new LinkedList())->remove('X'));
    }

    public function testRemoveOnlyRemovesTheFirstOccurrence(): void
    {
        $list = new LinkedList(['A', 'B', 'A']);
        $this->assertTrue($list->remove('A'));
        $this->assertSame(['B', 'A'], $list->toArray());
    }

    public function testRemoveTheOnlyItem(): void
    {
        $list = new LinkedList(['A']);
        $this->assertTrue($list->remove('A'));
        $this->assertTrue($list->isEmpty());
        $this->assertSame([], $list->toArray());
    }

    public function testToArray(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame([], (new LinkedList())->toArray());
    }
    #endregion

    #region Sequenced collection
    public function testAddFirst(): void
    {
        $this->list->addFirst('X');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('X', $this->list->getFirst());
        $this->assertSame('E', $this->list->getLast());
        $this->assertSame(['X', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame(['X', 'A', 'B', 'C', 'D', 'E'], iterator_to_array($this->list));
    }

    public function testAddFirstRepeatedly(): void
    {
        $list = new LinkedList();
        $list->addFirst('C');
        $list->addFirst('B');
        $list->addFirst('A');
        $this->assertSame(['A', 'B', 'C'], $list->toArray());
        $this->assertSame('A', $list->getFirst());
        $this->assertSame('C', $list->getLast());
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
        $this->assertSame('A', $this->list->getFirst());
        $this->assertSame('H', $this->list->getLast());
    }

    public function testGetFirst(): void
    {
        $this->assertSame('A', $this->list->getFirst());
    }

    public function testGetFirstOnEmptyListThrows(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new LinkedList())->getFirst();
    }

    public function testGetLast(): void
    {
        $this->assertSame('E', $this->list->getLast());
    }

    public function testGetLastOnEmptyListThrows(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new LinkedList())->getLast();
    }

    public function testGetFirstAndGetLastOnSingleItemList(): void
    {
        $list = new LinkedList(['A']);
        $this->assertSame('A', $list->getFirst());
        $this->assertSame('A', $list->getLast());
    }

    public function testRemoveFirst(): void
    {
        $this->assertSame('A', $this->list->removeFirst());
        $this->assertSame(4, $this->list->count());
        $this->assertSame('B', $this->list->getFirst());
        $this->assertSame('E', $this->list->getLast());
        $this->assertSame(['B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testRemoveFirstUntilEmpty(): void
    {
        $removed = [];
        while (!$this->list->isEmpty()) {
            $removed[] = $this->list->removeFirst();
        }

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $removed);
        $this->assertSame(0, $this->list->count());
    }

    public function testRemoveFirstOnEmptyListThrows(): void
    {
        $this->list->clear();
        $this->expectException(InvalidOperationException::class);
        $this->list->removeFirst();
    }

    public function testRemoveLast(): void
    {
        $this->assertSame('E', $this->list->removeLast());
        $this->assertSame(4, $this->list->count());
        $this->assertSame('A', $this->list->getFirst());
        $this->assertSame('D', $this->list->getLast());
        $this->assertSame(['A', 'B', 'C', 'D'], $this->list->toArray());
    }

    public function testRemoveLastUntilEmpty(): void
    {
        $removed = [];
        while (!$this->list->isEmpty()) {
            $removed[] = $this->list->removeLast();
        }

        $this->assertSame(['E', 'D', 'C', 'B', 'A'], $removed);
        $this->assertSame(0, $this->list->count());
    }

    public function testRemoveLastOnEmptyListThrows(): void
    {
        $this->list->clear();
        $this->expectException(InvalidOperationException::class);
        $this->list->removeLast();
    }

    public function testReverse(): void
    {
        $this->list->reverse();
        $this->assertSame(['E', 'D', 'C', 'B', 'A'], $this->list->toArray());
        $this->assertSame(['E', 'D', 'C', 'B', 'A'], iterator_to_array($this->list));
        $this->assertSame('E', $this->list->getFirst());
        $this->assertSame('A', $this->list->getLast());
        $this->assertSame(5, $this->list->count());
        $this->assertSame('D', $this->list->get(1));
        $this->assertSame('B', $this->list->get(3));
    }

    public function testReverseTwiceRestoresOriginalOrder(): void
    {
        $this->list->reverse();
        $this->list->reverse();
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testListIsUsableAfterReverse(): void
    {
        $this->list->reverse();
        $this->list->addFirst('X');
        $this->list->addLast('Y');
        $this->list->insert(3, 'Z');
        $this->list->removeAt(1);

        $this->assertSame(['X', 'D', 'Z', 'C', 'B', 'A', 'Y'], $this->list->toArray());
        $this->assertSame(6, $this->list->lastIndexOf('Y'));
    }

    public function testReverseTwoItems(): void
    {
        $list = new LinkedList(['A', 'B']);
        $list->reverse();
        $this->assertSame(['B', 'A'], $list->toArray());
    }

    public function testReverseSingleItemAndEmptyList(): void
    {
        $single = new LinkedList(['A']);
        $single->reverse();
        $this->assertSame(['A'], $single->toArray());

        $empty = new LinkedList();
        $empty->reverse();
        $this->assertSame([], $empty->toArray());
    }

    public function testReverseDuringIterationThrows(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $value) {
            $this->list->reverse();
        }
    }

    public function testToReversed(): void
    {
        $list = new LinkedList(['A', 'B', 'C', 'D']);
        $reversed = $list->toReversed();

        $this->assertInstanceOf(LinkedList::class, $reversed);
        $this->assertSame(['D', 'C', 'B', 'A'], $reversed->toArray());
        $this->assertSame(['D', 'C', 'B', 'A'], iterator_to_array($reversed));
        $this->assertSame('D', $reversed->getFirst());
        $this->assertSame('A', $reversed->getLast());

        // The original list is unchanged
        $this->assertSame(['A', 'B', 'C', 'D'], $list->toArray());
    }

    public function testToReversedReturnsAnIndependentList(): void
    {
        $reversed = $this->list->toReversed();
        $reversed->add('X');
        $this->list->removeFirst();

        $this->assertSame(['E', 'D', 'C', 'B', 'A', 'X'], $reversed->toArray());
        $this->assertSame(['B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testToReversedSingleItem(): void
    {
        $list = new LinkedList(['A']);
        $reversed = $list->toReversed();
        $this->assertSame(['A'], $reversed->toArray());
        $this->assertSame(['A'], $list->toArray());
    }

    public function testToReversedEmptyList(): void
    {
        $list = new LinkedList();
        $reversed = $list->toReversed();
        $this->assertInstanceOf(LinkedList::class, $reversed);
        $this->assertNotSame($list, $reversed);
        $this->assertTrue($reversed->isEmpty());
    }
    #endregion

    #region List collection
    public function testGet(): void
    {
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('D', $this->list->get(3));
        $this->assertSame('E', $this->list->get(4));
    }

    public static function invalidIndexProvider(): array
    {
        return [
            'negative' => [-1],
            'equal to count' => [5],
            'large' => [100],
        ];
    }

    #[DataProvider('invalidIndexProvider')]
    public function testGetFailsWhenIndexIsOutOfBounds(int $index): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->get($index);
    }

    public function testGetOnEmptyListThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        (new LinkedList())->get(0);
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

    public function testSetFirstAndLast(): void
    {
        $this->list->set(0, 'X');
        $this->list->set(4, 'Y');
        $this->assertSame(['X', 'B', 'C', 'D', 'Y'], $this->list->toArray());
        $this->assertSame('X', $this->list->getFirst());
        $this->assertSame('Y', $this->list->getLast());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testSetFailsWhenIndexIsOutOfBounds(int $index): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->set($index, null);
    }

    public function testInsertFirst(): void
    {
        $this->list->insert(0, 'First');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('First', $this->list->get(0));
        $this->assertSame('A', $this->list->get(1));
        $this->assertSame('B', $this->list->get(2));
        $this->assertSame('C', $this->list->get(3));
        $this->assertSame('D', $this->list->get(4));
        $this->assertSame('E', $this->list->get(5));
        $this->assertSame('First', $this->list->getFirst());
        $this->assertSame('E', $this->list->getLast());
    }

    public function testInsertAtStartThenMiddleOnNonEmptyList(): void
    {
        $list = new LinkedList(['A', 'B', 'C']);
        $list->insert(0, 'X');
        $list->insert(2, 'Y');

        $this->assertSame(['X', 'A', 'Y', 'B', 'C'], $list->toArray());
        $this->assertSame(['X', 'A', 'Y', 'B', 'C'], iterator_to_array($list));
        $this->assertSame('X', $list->getFirst());
        $this->assertSame('C', $list->getLast());
        $this->assertSame(5, $list->count());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testInsertFailsWhenIndexIsOutOfBounds(int $index): void
    {
        if ($index === 5) {
            // Inserting at count() is valid (appends)
            $index = 6;
        }

        $this->expectException(OutOfBoundsException::class);
        $this->list->insert($index, null);
    }

    public function testInsertBetween(): void
    {
        $this->list->insert(3, 'Between');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('Between', $this->list->get(3));
        $this->assertSame('D', $this->list->get(4));
        $this->assertSame('E', $this->list->get(5));
    }

    public function testInsertBeforeLastItem(): void
    {
        $this->list->insert(4, 'X');
        $this->assertSame(['A', 'B', 'C', 'D', 'X', 'E'], $this->list->toArray());
        $this->assertSame('E', $this->list->getLast());
    }

    public function testInsertLast(): void
    {
        $this->list->insert($this->list->count(), 'Last');
        $this->assertSame(6, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('C', $this->list->get(2));
        $this->assertSame('D', $this->list->get(3));
        $this->assertSame('E', $this->list->get(4));
        $this->assertSame('Last', $this->list->get(5));
        $this->assertSame('Last', $this->list->getLast());
    }

    public function testInsertOnEmptyList(): void
    {
        $list = new LinkedList();
        $list->insert(0, 'ABC');
        $this->assertSame('ABC', $list->get(0));
        $this->assertSame(1, $list->count());
        $this->assertSame('ABC', $list->getFirst());
        $this->assertSame('ABC', $list->getLast());
    }

    public function testInsertOnEmptyListThrowsWhenTheIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $list = new LinkedList();
        $list->insert(10, 'ABC');
    }

    public function testInsertOnEmptyListThrowsWhenTheIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $list = new LinkedList();
        $list->insert(-1, 'ABC');
    }

    public function testInsertRange_First(): void
    {
        $this->list->insertAll(0, ['X', 'Y', 'Z']); // [X, Y, Z, A, B, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['X', 'Y', 'Z', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame(['X', 'Y', 'Z', 'A', 'B', 'C', 'D', 'E'], iterator_to_array($this->list));
        $this->assertSame('X', $this->list->getFirst());
        $this->assertSame('E', $this->list->getLast());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testInsertRange_FailsWhenIndexIsOutOfBounds(int $index): void
    {
        if ($index === 5) {
            // Inserting at count() is valid (appends)
            $index = 6;
        }

        $this->expectException(OutOfBoundsException::class);
        $this->list->insertAll($index, ['X']);
    }

    public function testInsertRange_FailsWhenIndexIsOutOfBoundsEvenWithEmptyIterable(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insertAll(100, []);
    }

    public function testInsertRange_Between(): void
    {
        $this->list->insertAll(2, ['X', 'Y', 'Z']); // [A, B, X, Y, Z, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'B', 'X', 'Y', 'Z', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame('A', $this->list->getFirst());
        $this->assertSame('E', $this->list->getLast());
    }

    public function testInsertRange_Last(): void
    {
        $this->list->insertAll(5, ['X', 'Y', 'Z']); // [A, B, C, D, E, X, Y, Z]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X', 'Y', 'Z'], $this->list->toArray());
        $this->assertSame('A', $this->list->getFirst());
        $this->assertSame('Z', $this->list->getLast());
    }

    public function testInsertRange_OnEmptyList(): void
    {
        $list = new LinkedList();
        $list->insertAll(0, ['X', 'Y', 'Z']);
        $this->assertSame(['X', 'Y', 'Z'], $list->toArray());
        $this->assertSame('X', $list->getFirst());
        $this->assertSame('Z', $list->getLast());
        $this->assertSame(3, $list->count());
    }

    public function testInsertRange_OnEmptyListThrowsWhenTheIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        (new LinkedList())->insertAll(1, ['X']);
    }

    public function testInsertRange_WithEmptyIterable(): void
    {
        $this->list->insertAll(2, []);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertRange_SingleItemAtStart(): void
    {
        $this->list->insertAll(0, ['X']);
        $this->assertSame(['X', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
        $this->assertSame('X', $this->list->getFirst());
    }

    public function testInsertRange_UsingAnAssociativeArray(): void
    {
        $this->list->insertAll(1, ['foo' => 'X', 'bar' => 'Y', 'baz' => 'Z']);
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'X', 'Y', 'Z', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertRange_UsingAnotherList(): void
    {
        $this->list->insertAll(3, new LinkedList(['X', 'Y']));
        $this->assertSame(['A', 'B', 'C', 'X', 'Y', 'D', 'E'], $this->list->toArray());
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

    public function testRemoveAt_FirstAndLast(): void
    {
        $this->list->removeAt(0);
        $this->list->removeAt(3);
        $this->assertSame(['B', 'C', 'D'], $this->list->toArray());
        $this->assertSame('B', $this->list->getFirst());
        $this->assertSame('D', $this->list->getLast());
    }

    public function testRemoveAt_OnlyItem(): void
    {
        $list = new LinkedList(['A']);
        $list->removeAt(0);
        $this->assertTrue($list->isEmpty());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testRemoveAt_FailsWhenIndexIsOutOfBounds(int $index): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeAt($index);
    }

    public function testIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(2, $list->indexOf('C'));
        $this->assertSame(0, $list->indexOf('A'));
        $this->assertSame(5, $list->indexOf('E'));
    }

    public function testIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->indexOf('Z'));
    }

    public function testIndexOf_OnEmptyList(): void
    {
        $this->assertSame(-1, (new LinkedList())->indexOf('A'));
    }

    public function testIndexOf_UsesStrictComparison(): void
    {
        $list = new LinkedList([0, '1', 1]);
        $this->assertSame(2, $list->indexOf(1));
        $this->assertSame(1, $list->indexOf('1'));
        $this->assertSame(-1, $list->indexOf('0'));
    }

    public function testLastIndexOf_FindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(3, $list->lastIndexOf('C'));
        $this->assertSame(0, $list->lastIndexOf('A'));
        $this->assertSame(5, $list->lastIndexOf('E'));
    }

    public function testLastIndexOf_DidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new LinkedList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->lastIndexOf('Z'));
    }

    public function testLastIndexOf_OnEmptyList(): void
    {
        $this->assertSame(-1, (new LinkedList())->lastIndexOf('A'));
    }

    public function testLastIndexOf_SingleItemList(): void
    {
        $list = new LinkedList(['A']);
        $this->assertSame(0, $list->lastIndexOf('A'));
        $this->assertSame(-1, $list->lastIndexOf('B'));
    }
    #endregion

    #region ArrayAccess
    public function testOffsetExists(): void
    {
        $this->assertTrue(isset($this->list[0]));
        $this->assertTrue(isset($this->list[4]));
        $this->assertFalse(isset($this->list[5]));
        $this->assertFalse(isset($this->list[-1]));
        $this->assertFalse(isset((new LinkedList())[0]));
    }

    public function testOffsetExistsWithNonIntOffsetReturnsFalse(): void
    {
        $this->assertFalse(isset($this->list['0']));
        $this->assertFalse(isset($this->list['A']));
        $this->assertFalse($this->list->offsetExists(null));
        $this->assertFalse($this->list->offsetExists(1.0));
    }

    public function testOffsetGet(): void
    {
        $this->assertSame('A', $this->list[0]);
        $this->assertSame('C', $this->list[2]);
        $this->assertSame('E', $this->list[4]);
    }

    public function testOffsetGetFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[5];
    }

    public function testOffsetGetFailsWithNegativeIndex(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[-1];
    }

    public function testOffsetGetFailsWithNonIntOffset(): void
    {
        $this->expectException(TypeError::class);
        $this->list['1'];
    }

    public function testOffsetSet(): void
    {
        $this->list[1] = 'X';
        $this->list[4] = 'Y';
        $this->assertSame(['A', 'X', 'C', 'D', 'Y'], $this->list->toArray());
        $this->assertSame(5, $this->list->count());
    }

    public function testOffsetSetWithNullOffsetAppends(): void
    {
        $this->list[] = 'F';
        $this->list[] = 'G';
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'F', 'G'], $this->list->toArray());
        $this->assertSame('G', $this->list->getLast());
    }

    public function testOffsetSetWithNullOffsetOnEmptyList(): void
    {
        $list = new LinkedList();
        $list[] = 'A';
        $this->assertSame(['A'], $list->toArray());
    }

    public function testOffsetSetFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[5] = 'X';
    }

    public function testOffsetSetFailsWithNonIntOffset(): void
    {
        $this->expectException(TypeError::class);
        $this->list['1'] = 'X';
    }

    public function testOffsetUnset(): void
    {
        unset($this->list[0]);
        unset($this->list[2]);
        $this->assertSame(['B', 'C', 'E'], $this->list->toArray());
    }

    public function testOffsetUnsetFailsWhenIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        unset($this->list[5]);
    }

    public function testOffsetUnsetFailsWithNonIntOffset(): void
    {
        $this->expectException(TypeError::class);
        unset($this->list['1']);
    }
    #endregion
}
