<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Generator;
use Neko\Collections\ArrayList;
use Neko\Collections\ConcurrentMutationException;
use Neko\Collections\Dictionary;
use Neko\InvalidOperationException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use TypeError;
use function count;
use function implode;
use function iterator_to_array;
use function ord;
use function range;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
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

    #region Constructor
    public function testConstructorWithoutArguments(): void
    {
        $list = new ArrayList();
        $this->assertTrue($list->isEmpty());
        $this->assertSame(0, $list->count());
        $this->assertSame([], $list->toArray());
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

    public function testConstructorWithAssociativeArrayDiscardsKeys(): void
    {
        $list = new ArrayList(['foo' => 'X', 'bar' => 'Y']);
        $this->assertSame(['X', 'Y'], $list->toArray());
        $this->assertSame('X', $list->get(0));
        $this->assertSame('Y', $list->get(1));
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

    public function testConstructorWithGeneratorArgument(): void
    {
        $generator = (function (): Generator {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'a' => 3;
        })();

        $list = new ArrayList($generator);
        $this->assertSame([1, 2, 3], $list->toArray());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $argument = new Dictionary(['k' => 'v']);
        $argument->set('a', 'b');

        $list = new ArrayList($argument);
        $this->assertSame(2, $list->count());
        $this->assertSame('v', $list->get(0));
        $this->assertSame('b', $list->get(1));
    }
    #endregion

    #region Serialization
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
        $this->assertSame([], $restored->get(2));
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
        $this->assertTrue($restored->isEmpty());
    }

    public function testSerializeRoundTripAfterRemovals(): void
    {
        $this->list->removeAt(1);   // [A, C, D, E]
        $this->list->removeLast();  // [A, C, D]

        $restored = unserialize(serialize($this->list));
        $this->assertInstanceOf(ArrayList::class, $restored);
        $this->assertSame(3, $restored->count());
        $this->assertSame(['A', 'C', 'D'], $restored->toArray());

        // The restored list must remain fully usable.
        $restored->add('Z');
        $this->assertSame(['A', 'C', 'D', 'Z'], $restored->toArray());
    }
    #endregion

    #region Iteration
    public function testIterator(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], iterator_to_array($this->list));
    }

    public function testIteratorOverEmptyList(): void
    {
        $this->assertSame([], iterator_to_array(new ArrayList()));
    }

    public function testIteratorDoesNotYieldRemovedItems(): void
    {
        $this->list->removeAt(4);
        $this->list->removeAt(3);
        $this->assertSame(['A', 'B', 'C'], iterator_to_array($this->list));
    }

    public function testIteratorThrowsExceptionIfTheListIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $char) {
            if ($char === 'C') {
                $this->list->add('D');
            }
        }
    }

    public function testIteratorThrowsExceptionIfAnItemIsReplaced(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $char) {
            if ($char === 'A') {
                $this->list->set(0, 'X');
            }
        }
    }

    public function testIteratorThrowsExceptionIfAnItemIsRemoved(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $char) {
            if ($char === 'B') {
                $this->list->remove('B');
            }
        }
    }

    public function testIteratorThrowsExceptionIfTheListIsModifiedOnTheLastItem(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->list as $char) {
            if ($char === 'E') {
                $this->list->clear();
            }
        }
    }

    public function testConcurrentMutationCanBeCaughtAsInvalidOperationException(): void
    {
        // ConcurrentMutationException extends InvalidOperationException, which iteration used to throw
        $this->expectException(InvalidOperationException::class);

        foreach ($this->list as $char) {
            $this->list->add($char);
        }
    }
    #endregion

    #region ArrayAccess
    public function testArrayAccessOffsetSetAppendsWhenOffsetIsNull(): void
    {
        $size = $this->list->count();
        $this->list[] = 'X';
        $this->assertSame($size + 1, $this->list->count());
        $this->assertSame('X', $this->list[$size]);
        $this->assertSame('X', $this->list->getLast());
    }

    public function testArrayAccessOffsetSetReplacesItem(): void
    {
        $this->list[2] = 'X';
        $this->assertSame(5, $this->list->count());
        $this->assertSame(['A', 'B', 'X', 'D', 'E'], $this->list->toArray());
    }

    public function testArrayAccessOffsetSetThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[5] = 'X';
    }

    public function testArrayAccessOffsetSetThrowsExceptionIfOffsetIsNotAnInteger(): void
    {
        $this->expectException(TypeError::class);
        $this->list['key'] = 'X';
    }

    public function testArrayAccessOffsetGet(): void
    {
        $this->assertSame('A', $this->list[0]);
        $this->assertSame('C', $this->list[2]);
        $this->assertSame('E', $this->list[4]);
    }

    public function testArrayAccessOffsetGetThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[5];
    }

    public function testArrayAccessOffsetGetThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list[-1];
    }

    public function testArrayAccessOffsetGetThrowsExceptionIfOffsetIsNotAnInteger(): void
    {
        $this->expectException(TypeError::class);
        $this->list['1'];
    }

    public function testArrayAccessOffsetExists(): void
    {
        $this->assertTrue(isset($this->list[0]));
        $this->assertTrue(isset($this->list[4]));
        $this->assertFalse(isset($this->list[5]));
        $this->assertFalse(isset($this->list[100]));
        $this->assertFalse(isset($this->list[-1]));
        $this->assertFalse(isset($this->list['key']));
        $this->assertFalse(isset($this->list['0']));
    }

    public function testArrayAccessOffsetExistsAfterRemoval(): void
    {
        $this->list->removeLast();
        $this->assertFalse(isset($this->list[4]));
        $this->assertTrue(isset($this->list[3]));
    }

    public function testArrayAccessOffsetExistsIsFalseForNullItems(): void
    {
        // Matches isset() on arrays: the index exists, but holds null
        $list = new ArrayList(['A', null, false, 0, '']);
        $this->assertFalse(isset($list[1]));
        $this->assertNull($list[1]);
        $this->assertSame(5, $list->count());

        // Falsy values that are not null are set
        $this->assertTrue(isset($list[2]));
        $this->assertTrue(isset($list[3]));
        $this->assertTrue(isset($list[4]));
    }

    public function testArrayAccessCannotModifyStoredArraysInPlace(): void
    {
        $list = new ArrayList([['x']]);
        $notices = [];
        set_error_handler(function (int $errno, string $message) use (&$notices): bool {
            $notices[] = $message;
            return true;
        });

        try {
            $list[0][] = 'y';
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $notices);
        $this->assertStringContainsString('Indirect modification', $notices[0]);
        $this->assertSame(['x'], $list[0]);

        // The documented way: read, modify, and assign back
        $item = $list[0];
        $item[] = 'y';
        $list[0] = $item;
        $this->assertSame(['x', 'y'], $list[0]);
    }

    /**
     * Operations that remove items, each leaving the list as [A, B, C].
     *
     * @return array<string, array{callable(ArrayList): mixed}>
     */
    public static function removalProvider(): array
    {
        return [
            'removeAt'    => [fn(ArrayList $l) => [$l->removeAt(4), $l->removeAt(3)]],
            'removeRange' => [fn(ArrayList $l) => $l->removeRange(3)],
            'removeIf'    => [fn(ArrayList $l) => $l->removeIf(fn($x) => $x === 'D' || $x === 'E')],
            'removeLast'  => [fn(ArrayList $l) => [$l->removeLast(), $l->removeLast()]],
            'remove'      => [fn(ArrayList $l) => [$l->remove('D'), $l->remove('E')]],
            'unset'       => [function (ArrayList $l) { unset($l[4], $l[3]); }],
        ];
    }

    #[DataProvider('removalProvider')]
    public function testRemovalsDoNotLeaveUnusedSlots(callable $remove): void
    {
        $remove($this->list);

        // The internal array must hold exactly the remaining items, with sequential keys
        $items = (new ReflectionProperty(ArrayList::class, 'items'))->getValue($this->list);
        $this->assertSame(['A', 'B', 'C'], $items);
        $this->assertSame(3, $this->list->count());

        // So the serialized form only carries the remaining items too
        $restored = unserialize(serialize($this->list));
        $this->assertSame(['A', 'B', 'C'], (new ReflectionProperty(ArrayList::class, 'items'))->getValue($restored));
    }

    public function testArrayAccessOffsetUnset(): void
    {
        unset($this->list[1]);
        $this->assertSame(4, $this->list->count());
        $this->assertSame(['A', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testArrayAccessOffsetUnsetThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        unset($this->list[5]);
    }

    public function testArrayAccessOffsetUnsetThrowsExceptionIfOffsetIsNotAnInteger(): void
    {
        $this->expectException(TypeError::class);
        unset($this->list['key']);
    }
    #endregion

    #region Basic operations
    public function testIsEmpty(): void
    {
        $this->assertFalse($this->list->isEmpty());
        $this->assertTrue((new ArrayList())->isEmpty());
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
    }

    public function testListIsUsableAfterClear(): void
    {
        $this->list->clear();
        $this->list->add('X');
        $this->assertSame(['X'], $this->list->toArray());
    }

    public function testContains(): void
    {
        $this->assertTrue($this->list->contains('C'));
        $this->assertFalse($this->list->contains('X'));
    }

    public function testContainsUsesStrictComparison(): void
    {
        $list = new ArrayList([1, 2, 3]);
        $this->assertTrue($list->contains(1));
        $this->assertFalse($list->contains('1'));
        $this->assertFalse($list->contains(1.0));
    }

    public function testContainsDoesNotFindRemovedItems(): void
    {
        $list = new ArrayList(['A', null, 'C']);
        $list->removeAt(1); // [A, C] (+ a stale slot)
        $this->assertFalse($list->contains(null));
    }

    public function testContainsAllWithArray(): void
    {
        $this->assertTrue($this->list->containsAll(['B', 'C', 'D']));
        $this->assertFalse($this->list->containsAll(['X', 'Y', 'Z']));
        $this->assertFalse($this->list->containsAll(['A', 'Z']));
    }

    public function testContainsAllWithCollection(): void
    {
        $this->assertTrue($this->list->containsAll(new ArrayList(['B', 'C', 'D'])));
        $this->assertFalse($this->list->containsAll(new ArrayList(['X', 'Y', 'Z'])));
    }

    public function testContainsAllWithEmptyIterableReturnsTrue(): void
    {
        $this->assertTrue($this->list->containsAll([]));
        $this->assertTrue((new ArrayList())->containsAll([]));
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

    public function testAddAfterRemovalsReusesSlots(): void
    {
        $this->list->removeRange(2); // [A, B]
        $this->list->add('X');
        $this->assertSame(3, $this->list->count());
        $this->assertSame(['A', 'B', 'X'], $this->list->toArray());
    }

    public function testAddAll(): void
    {
        $this->list->addAll(['X', 'Y']);
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X', 'Y'], $this->list->toArray());
    }

    public function testAddAllWithCollection(): void
    {
        $this->list->addAll(new ArrayList(['X', 'Y']));
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X', 'Y'], $this->list->toArray());
    }

    public function testAddAllWithItself(): void
    {
        $this->list->addAll($this->list);
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testAddAllWithEmptyIterable(): void
    {
        $this->list->addAll([]);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testAddAllToEmptyList(): void
    {
        $list = new ArrayList();
        $list->addAll(['X', 'Y']);
        $this->assertSame(['X', 'Y'], $list->toArray());
    }

    public function testAddFirst(): void
    {
        $this->list->addFirst('X');
        $this->assertSame(6, $this->list->count());
        $this->assertSame(['X', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testAddFirstToEmptyList(): void
    {
        $list = new ArrayList();
        $list->addFirst('X');
        $this->assertSame(['X'], $list->toArray());
    }

    public function testAddLast(): void
    {
        $this->list->addLast('X');
        $this->assertSame(6, $this->list->count());
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X'], $this->list->toArray());
    }

    public function testGet(): void
    {
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('E', $this->list->get(4));
    }

    public function testGetThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->get(100);
    }

    public function testGetThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->get(-1);
    }

    public function testGetThrowsExceptionIfIndexWasRemoved(): void
    {
        $this->list->removeLast();
        $this->expectException(OutOfBoundsException::class);
        $this->list->get(4);
    }

    public function testSet(): void
    {
        $copy = clone $this->list;

        // Replace a value
        $copy->set(1, 'X');
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('X', $copy->get(1));
        $this->assertSame(5, $copy->count());
    }

    public function testSetThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->set(100, null);
    }

    public function testSetThrowsExceptionIfIndexEqualsCount(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->set(5, 'X');
    }

    public function testSetThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->set(-1, 'X');
    }

    public function testGetFirst(): void
    {
        $this->assertSame('A', $this->list->getFirst());
        $this->assertSame(5, $this->list->count());
    }

    public function testGetFirstThrowsExceptionIfListIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new ArrayList())->getFirst();
    }

    public function testGetLast(): void
    {
        $this->assertSame('E', $this->list->getLast());
        $this->assertSame(5, $this->list->count());
    }

    public function testGetLastAfterRemovals(): void
    {
        $this->list->removeAt(4);
        $this->assertSame('D', $this->list->getLast());
    }

    public function testGetLastThrowsExceptionIfListIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new ArrayList())->getLast();
    }

    public function testGetLastThrowsExceptionAfterAllItemsWereRemoved(): void
    {
        $this->list->removeRange(0);
        $this->expectException(InvalidOperationException::class);
        $this->list->getLast();
    }
    #endregion

    #region Insert
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
    }

    public function testInsertIntoEmptyList(): void
    {
        $list = new ArrayList();
        $list->insert(0, 'X');
        $this->assertSame(['X'], $list->toArray());
    }

    public function testInsertAfterRemovals(): void
    {
        $this->list->removeRange(3); // [A, B, C]
        $this->list->insert(1, 'X');
        $this->assertSame(['A', 'X', 'B', 'C'], $this->list->toArray());
    }

    public function testInsertThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insert(100, null);
    }

    public function testInsertThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insert(-1, 'X');
    }

    public function testInsertAllFirst(): void
    {
        $this->list->insertAll(0, ['X', 'Y', 'Z']); // [X, Y, Z, A, B, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['X', 'Y', 'Z', 'A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertAllBetween(): void
    {
        $this->list->insertAll(2, ['X', 'Y', 'Z']); // [A, B, X, Y, Z, C, D, E]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'B', 'X', 'Y', 'Z', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertAllLast(): void
    {
        $this->list->insertAll(5, ['X', 'Y', 'Z']); // [A, B, C, D, E, X, Y, Z]
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'X', 'Y', 'Z'], $this->list->toArray());
    }

    public function testInsertAllUsingAnAssociativeArray(): void
    {
        $this->list->insertAll(1, ['foo' => 'X', 'bar' => 'Y', 'baz' => 'Z']);
        $this->assertSame(8, $this->list->count());
        $this->assertSame(['A', 'X', 'Y', 'Z', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertAllUsingATraversable(): void
    {
        $this->list->insertAll(1, new ArrayList(['X', 'Y']));
        $this->assertSame(['A', 'X', 'Y', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertAllWithEmptyIterable(): void
    {
        $this->list->insertAll(2, []);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testInsertAllAfterRemovals(): void
    {
        $this->list->removeRange(2); // [A, B]
        $this->list->insertAll(1, ['X', 'Y', 'Z', 'W']);
        $this->assertSame(6, $this->list->count());
        $this->assertSame(['A', 'X', 'Y', 'Z', 'W', 'B'], $this->list->toArray());
    }

    public function testInsertAllThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insertAll(100, []);
    }

    public function testInsertAllThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->insertAll(-1, ['X']);
    }
    #endregion

    #region Remove
    public function testRemove(): void
    {
        $list = new ArrayList(['A', 'B', 'C', 'B']);
        $this->assertTrue($list->remove('B'));
        $this->assertSame(['A', 'C', 'B'], $list->toArray());
    }

    public function testRemoveReturnsFalseIfItemIsNotFound(): void
    {
        $this->assertFalse($this->list->remove('X'));
        $this->assertSame(5, $this->list->count());
    }

    public function testRemoveUsesStrictComparison(): void
    {
        $list = new ArrayList([1, 2, 3]);
        $this->assertFalse($list->remove('2'));
        $this->assertSame(3, $list->count());
    }

    public function testRemoveAt(): void
    {
        $this->list->removeAt(2); // [A, B, D, E]
        $this->assertSame(4, $this->list->count());
        $this->assertSame('A', $this->list->get(0));
        $this->assertSame('B', $this->list->get(1));
        $this->assertSame('D', $this->list->get(2));
        $this->assertSame('E', $this->list->get(3));
    }

    public function testRemoveAtFirstAndLast(): void
    {
        $this->list->removeAt(0);
        $this->list->removeAt(3);
        $this->assertSame(['B', 'C', 'D'], $this->list->toArray());
    }

    public function testRemoveAtThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeAt(100);
    }

    public function testRemoveAtThrowsExceptionIfIndexEqualsCount(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeAt(5);
    }

    public function testRemoveAtThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeAt(-1);
    }

    public function testRemoveFirst(): void
    {
        $this->assertSame('A', $this->list->removeFirst());
        $this->assertSame(['B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testRemoveFirstThrowsExceptionIfListIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new ArrayList())->removeFirst();
    }

    public function testRemoveLast(): void
    {
        $this->assertSame('E', $this->list->removeLast());
        $this->assertSame('D', $this->list->removeLast());
        $this->assertSame(['A', 'B', 'C'], $this->list->toArray());
    }

    public function testRemoveLastThrowsExceptionIfListIsEmpty(): void
    {
        $this->expectException(InvalidOperationException::class);
        (new ArrayList())->removeLast();
    }

    public function testRemoveLastThrowsExceptionAfterAllItemsWereRemoved(): void
    {
        $list = new ArrayList(['A']);
        $list->removeLast();
        $this->expectException(InvalidOperationException::class);
        $list->removeLast();
    }

    public function testRemoveRangeFirst(): void
    {
        $this->list->removeRange(0, 3); // [D, E]
        $this->assertSame(2, $this->list->count());
        $this->assertSame(['D', 'E'], $this->list->toArray());
    }

    public function testRemoveRangeBetween(): void
    {
        // Remove B, C, D
        $this->list->removeRange(1, 3); // [A, E]
        $this->assertSame(2, $this->list->count());
        $this->assertSame(['A', 'E'], $this->list->toArray());
    }

    public function testRemoveRangeLast(): void
    {
        // Remove D, E
        $this->list->removeRange(3, 2); // [A, B, C]
        $this->assertSame(3, $this->list->count());
        $this->assertSame(['A', 'B', 'C'], $this->list->toArray());

        $this->expectException(OutOfBoundsException::class);
        $this->list->get(3);
    }

    public function testRemoveRangeWithoutCountParameter(): void
    {
        // Remove everything after B
        $removed = $this->list->removeRange(2); // [A, B]
        $this->assertSame(3, $removed);
        $this->assertSame(2, $this->list->count());
        $this->assertSame(['A', 'B'], $this->list->toArray());
    }

    public function testRemoveRangeReturnsNumberOfElementsRemoved(): void
    {
        $removed = $this->list->removeRange(0, 3);
        $this->assertSame(3, $removed);
    }

    public function testRemoveRangeRemovesAllValuesIfCountIsLargerThanTheSizeOfTheList(): void
    {
        $removed = $this->list->removeRange(0, 10000);
        $this->assertSame(5, $removed);
        $this->assertSame(0, $this->list->count());
        $this->assertTrue($this->list->isEmpty());
    }

    public function testRemoveRangeRemovesRemainingValuesIfCountIsLargerThanTheSizeOfTheList(): void
    {
        $removed = $this->list->removeRange(4, 1000);
        $this->assertSame(1, $removed);
        $this->assertSame(4, $this->list->count());
        $this->assertSame(['A', 'B', 'C', 'D'], $this->list->toArray());
    }

    public function testRemoveRangeWithZeroOrNegativeCountRemovesNothing(): void
    {
        $this->assertSame(0, $this->list->removeRange(1, 0));
        $this->assertSame(0, $this->list->removeRange(1, -2));
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testRemoveRangeThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeRange(100, 100);
    }

    public function testRemoveRangeThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->removeRange(-1, 1);
    }

    public function testRemoveIf(): void
    {
        // Removes B (66) and D (68)
        $count = $this->list->removeIf(fn($c) => ord($c) % 2 === 0);
        $this->assertSame(2, $count);
        $this->assertSame(3, $this->list->count());
        $this->assertSame(['A', 'C', 'E'], $this->list->toArray());
    }

    public function testRemoveIfReturnsZeroIfNothingMatches(): void
    {
        $count = $this->list->removeIf(fn() => false);
        $this->assertSame(0, $count);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testRemoveIfRemovesEverything(): void
    {
        $count = $this->list->removeIf(fn() => true);
        $this->assertSame(5, $count);
        $this->assertTrue($this->list->isEmpty());
        $this->assertSame([], $this->list->toArray());
    }

    public function testRemoveIfOnEmptyList(): void
    {
        $this->assertSame(0, (new ArrayList())->removeIf(fn() => true));
    }

    public function testRemoveIfLeavesTheListUnchangedIfThePredicateThrows(): void
    {
        $list = new ArrayList(['A', 'B', 'C', 'D']);
        $predicate = function (string $item): bool {
            if ($item === 'D') {
                throw new RuntimeException('Predicate failed');
            }

            return $item === 'A';
        };

        try {
            $list->removeIf($predicate);
            $this->fail('The exception thrown by the predicate was not propagated');
        } catch (RuntimeException $e) {
            $this->assertSame('Predicate failed', $e->getMessage());
        }

        $this->assertSame(4, $list->count());
        $this->assertSame(['A', 'B', 'C', 'D'], $list->toArray());
    }

    public function testRemoveIfDoesNotInvalidateIteratorsIfNothingIsRemoved(): void
    {
        $visited = [];
        foreach ($this->list as $item) {
            $this->list->removeIf(fn() => false);
            $visited[] = $item;
        }

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $visited);
    }
    #endregion

    #region Search
    public function testIndexOfFindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(2, $list->indexOf('C'));
    }

    public function testIndexOfDidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->indexOf('Z'));
    }

    public function testIndexOfOnEmptyList(): void
    {
        $this->assertSame(-1, (new ArrayList())->indexOf('A'));
    }

    public function testLastIndexOfFindsAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(3, $list->lastIndexOf('C'));
    }

    public function testLastIndexOfDidNotFindAValue(): void
    {
        //                      0    1    2    3    4    5
        $list = new ArrayList(['A', 'B', 'C', 'C', 'D', 'E']);
        $this->assertSame(-1, $list->lastIndexOf('Z'));
    }

    public function testLastIndexOfDoesNotFindRemovedItems(): void
    {
        $list = new ArrayList(['A', 'B', null]);
        $list->removeAt(1); // [A, null] + stale slot
        $list->removeAt(1); // [A] + stale slots
        $this->assertSame(-1, $list->lastIndexOf(null));
    }

    public function testBinarySearchFindsAValue(): void
    {
        $this->assertSame(0, $this->list->binarySearch('A'));
        $this->assertSame(1, $this->list->binarySearch('B'));
        $this->assertSame(2, $this->list->binarySearch('C'));
        $this->assertSame(3, $this->list->binarySearch('D'));
        $this->assertSame(4, $this->list->binarySearch('E'));
    }

    public function testBinarySearchDidNotFindAValue(): void
    {
        $this->assertSame(-1, $this->list->binarySearch('0'));
        $this->assertSame(-1, $this->list->binarySearch('BB'));
        $this->assertSame(-1, $this->list->binarySearch('X'));
        $this->assertSame(-1, $this->list->binarySearch('Z'));
    }

    public function testBinarySearchOnEmptyList(): void
    {
        $this->assertSame(-1, (new ArrayList())->binarySearch('A'));
    }

    public function testBinarySearchOnSingleItemList(): void
    {
        $list = new ArrayList([5]);
        $this->assertSame(0, $list->binarySearch(5));
        $this->assertSame(-1, $list->binarySearch(4));
        $this->assertSame(-1, $list->binarySearch(6));
    }

    public function testBinarySearchWithComparer(): void
    {
        $list = new ArrayList([50, 40, 30, 20, 10]); // sorted in descending order
        $comparer = fn($a, $b) => $b <=> $a;

        $this->assertSame(0, $list->binarySearch(50, $comparer));
        $this->assertSame(2, $list->binarySearch(30, $comparer));
        $this->assertSame(4, $list->binarySearch(10, $comparer));
        $this->assertSame(-1, $list->binarySearch(35, $comparer));
    }

    public function testBinarySearchWithComparerReturningFloats(): void
    {
        $list = new ArrayList([1.5, 2.25, 3.75, 4.0]);
        $comparer = fn(float $a, float $b): float => $a - $b;

        $this->assertSame(0, $list->binarySearch(1.5, $comparer));
        $this->assertSame(1, $list->binarySearch(2.25, $comparer));
        $this->assertSame(2, $list->binarySearch(3.75, $comparer));
        $this->assertSame(3, $list->binarySearch(4.0, $comparer));
        $this->assertSame(-1, $list->binarySearch(3.0, $comparer));
    }

    public function testBinarySearchIgnoresRemovedItems(): void
    {
        $list = new ArrayList([1, 2, 3, 4, 5]);
        $list->removeRange(3); // [1, 2, 3]
        $this->assertSame(-1, $list->binarySearch(4));
        $this->assertSame(-1, $list->binarySearch(5));
        $this->assertSame(2, $list->binarySearch(3));
    }

    public function testFindIndex(): void
    {
        $list = new ArrayList([1, 2, 3, 4, 5, 6]);
        $this->assertSame(1, $list->findIndex(fn($i) => $i % 2 === 0));
        $this->assertSame(0, $list->findIndex(fn() => true));
    }

    public function testFindIndexReturnsMinusOneIfNotFound(): void
    {
        $this->assertSame(-1, $this->list->findIndex(fn($c) => $c === 'X'));
        $this->assertSame(-1, (new ArrayList())->findIndex(fn() => true));
    }

    public function testFindIndexIgnoresRemovedItems(): void
    {
        $list = new ArrayList(['A', 'B', null]);
        $list->removeLast();
        $this->assertSame(-1, $list->findIndex(fn($x) => $x === null));
    }

    public function testFindLastIndex(): void
    {
        $list = new ArrayList([1, 2, 3, 4, 5, 6]);
        $this->assertSame(5, $list->findLastIndex(fn($i) => $i % 2 === 0));
        $this->assertSame(4, $list->findLastIndex(fn($i) => $i % 2 === 1));
    }

    public function testFindLastIndexReturnsMinusOneIfNotFound(): void
    {
        $this->assertSame(-1, $this->list->findLastIndex(fn($c) => $c === 'X'));
        $this->assertSame(-1, (new ArrayList())->findLastIndex(fn() => true));
    }

    public function testFindLastIndexIgnoresRemovedItems(): void
    {
        $this->list->removeAt(4);
        $this->list->removeAt(3);
        $this->assertSame(2, $this->list->findLastIndex(fn() => true));
    }
    #endregion

    #region Reverse
    public function testReverse(): void
    {
        $this->list->reverse();
        $this->assertSame(5, $this->list->count());
        $this->assertSame(['E', 'D', 'C', 'B', 'A'], $this->list->toArray());
    }

    public function testReverseWithEvenNumberOfItems(): void
    {
        $list = new ArrayList([1, 2, 3, 4]);
        $list->reverse();
        $this->assertSame([4, 3, 2, 1], $list->toArray());
    }

    public function testReverseEmptyList(): void
    {
        $list = new ArrayList();
        $list->reverse();
        $this->assertTrue($list->isEmpty());
    }

    public function testReverseAfterRemovals(): void
    {
        $this->list->removeAt(4);
        $this->list->removeAt(3);
        $this->list->reverse();
        $this->assertSame(['C', 'B', 'A'], $this->list->toArray());
    }

    public function testToReversed(): void
    {
        $reversed = $this->list->toReversed();
        $this->assertInstanceOf(ArrayList::class, $reversed);
        $this->assertSame(['E', 'D', 'C', 'B', 'A'], $reversed->toArray());
        // The original list is unchanged
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testToReversedEmptyList(): void
    {
        $reversed = (new ArrayList())->toReversed();
        $this->assertTrue($reversed->isEmpty());
        $this->assertSame([], $reversed->toArray());
    }

    public function testToReversedAfterRemoveAtDoesNotContainStaleItems(): void
    {
        $this->list->removeAt(4);
        $this->list->removeAt(3); // [A, B, C]

        $reversed = $this->list->toReversed();
        $this->assertSame(3, $reversed->count());
        $this->assertSame(['C', 'B', 'A'], $reversed->toArray());
        $this->assertSame(['C', 'B', 'A'], iterator_to_array($reversed));
        $this->assertFalse($reversed->contains(null));
    }

    public function testToReversedResultIsIndependentAndUsable(): void
    {
        $this->list->removeIf(fn($c) => $c === 'E');
        $reversed = $this->list->toReversed();
        $reversed->add('Z');
        $this->assertSame(['D', 'C', 'B', 'A', 'Z'], $reversed->toArray());
        $this->assertSame(['A', 'B', 'C', 'D'], $this->list->toArray());
    }
    #endregion

    #region Slice & range
    public function testSliceFromBeginningOfList(): void
    {
        $slice = $this->list->slice(0, 3);
        $this->assertSame(3, $slice->count());
        $this->assertSame(['A', 'B', 'C'], $slice->toArray());
    }

    public function testSliceFromMiddleOfList(): void
    {
        $slice = $this->list->slice(2, 2);
        $this->assertSame(2, $slice->count());
        $this->assertSame(['C', 'D'], $slice->toArray());
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
        $this->assertSame(['D', 'E'], $slice->toArray());
    }

    public function testSliceReturnsRestOfTheList(): void
    {
        $slice = $this->list->slice(0, 100);
        $this->assertSame(5, $slice->count());
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $slice->toArray());
    }

    public function testSliceWithoutCountReturnsRemainingItems(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->slice(0)->toArray());
        $this->assertSame(['C', 'D', 'E'], $this->list->slice(2)->toArray());
        $this->assertSame(['E'], $this->list->slice(4)->toArray());
    }

    public static function negativeCountSliceProvider(): array
    {
        // List is [A, B, C]
        return [
            'stops one before the end'     => [0, -1, ['A', 'B']],
            'stops two before the end'     => [0, -2, ['A']],
            'from middle, one before end'  => [1, -1, ['B']],
            'nothing left after index'     => [2, -1, []],
            'count larger than remaining'  => [1, -5, []],
            'count equals list size'       => [0, -3, []],
        ];
    }

    #[DataProvider('negativeCountSliceProvider')]
    public function testSliceWithNegativeCount(int $index, int $count, array $expected): void
    {
        $list = new ArrayList(['A', 'B', 'C']);
        $slice = $list->slice($index, $count);
        $this->assertSame(count($expected), $slice->count());
        $this->assertSame($expected, $slice->toArray());
    }

    public function testSliceIsIndependentOfTheOriginalList(): void
    {
        $slice = $this->list->slice(1, 2);
        $slice->set(0, 'X');
        $slice->add('Y');
        $this->assertSame(['X', 'C', 'Y'], $slice->toArray());
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testSliceThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->slice(6);
    }

    public function testSliceAtTheEndOfTheListReturnsAnEmptyList(): void
    {
        $this->assertSame([], $this->list->slice(5)->toArray());
        $this->assertSame([], $this->list->slice(5, 2)->toArray());
        $this->assertSame(0, $this->list->slice(5)->count());
    }

    public function testSliceOnEmptyListReturnsAnEmptyList(): void
    {
        $slice = (new ArrayList())->slice(0);
        $this->assertTrue($slice->isEmpty());
        $this->assertSame([], $slice->toArray());
    }

    public function testSliceThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->slice(-1, 2);
    }

    public static function sliceAfterRemovalsProvider(): array
    {
        // List is [A, B, C] after removing D and E
        return [
            'slice(0)'       => [0, null, ['A', 'B', 'C']],
            'slice(1)'       => [1, null, ['B', 'C']],
            'slice(1, 10)'   => [1, 10, ['B', 'C']],
            'slice(0, 10)'   => [0, 10, ['A', 'B', 'C']],
            'slice(0, -1)'   => [0, -1, ['A', 'B']],
            'slice(2, -1)'   => [2, -1, []],
            'slice(1, -5)'   => [1, -5, []],
        ];
    }

    #[DataProvider('sliceAfterRemovalsProvider')]
    public function testSliceAfterRemoveAtDoesNotContainStaleItems(int $index, ?int $count, array $expected): void
    {
        $this->list->removeAt(4);
        $this->list->removeAt(3); // [A, B, C]

        $slice = $this->list->slice($index, $count);
        $this->assertSame(count($expected), $slice->count());
        $this->assertSame($expected, $slice->toArray());
    }

    #[DataProvider('sliceAfterRemovalsProvider')]
    public function testSliceAfterRemoveRangeDoesNotContainStaleItems(int $index, ?int $count, array $expected): void
    {
        $this->list->removeRange(3); // [A, B, C]

        $slice = $this->list->slice($index, $count);
        $this->assertSame(count($expected), $slice->count());
        $this->assertSame($expected, $slice->toArray());
    }

    #[DataProvider('sliceAfterRemovalsProvider')]
    public function testSliceAfterRemoveIfDoesNotContainStaleItems(int $index, ?int $count, array $expected): void
    {
        $this->list->removeIf(fn($c) => $c === 'D' || $c === 'E'); // [A, B, C]

        $slice = $this->list->slice($index, $count);
        $this->assertSame(count($expected), $slice->count());
        $this->assertSame($expected, $slice->toArray());
    }

    #[DataProvider('sliceAfterRemovalsProvider')]
    public function testSliceAfterRemoveLastDoesNotContainStaleItems(int $index, ?int $count, array $expected): void
    {
        $this->list->removeLast();
        $this->list->removeLast(); // [A, B, C]

        $slice = $this->list->slice($index, $count);
        $this->assertSame(count($expected), $slice->count());
        $this->assertSame($expected, $slice->toArray());
    }

    public function testRange(): void
    {
        $this->assertSame(['B', 'C', 'D'], $this->list->range(1, 4)->toArray());
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->range(0, 5)->toArray());
        $this->assertSame(['E'], $this->list->range(4, 5)->toArray());
    }

    public function testRangeWithEqualIndexesReturnsEmptyList(): void
    {
        $range = $this->list->range(2, 2);
        $this->assertTrue($range->isEmpty());
        $this->assertSame([], $range->toArray());
    }

    public function testRangeAfterRemovals(): void
    {
        $this->list->removeRange(3); // [A, B, C]
        $this->assertSame(['B', 'C'], $this->list->range(1, 3)->toArray());
    }

    public function testRangeThrowsExceptionIfFromIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->range(6, 6);
    }

    public function testRangeAtTheEndOfTheListReturnsAnEmptyList(): void
    {
        $this->assertSame([], $this->list->range(5, 5)->toArray());
    }

    public function testRangeOnEmptyListReturnsAnEmptyList(): void
    {
        $range = (new ArrayList())->range(0, 0);
        $this->assertTrue($range->isEmpty());
        $this->assertSame([], $range->toArray());
    }

    public function testRangeThrowsExceptionIfFromIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->range(-1, 2);
    }

    public function testRangeThrowsExceptionIfToIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->range(0, 6);
    }

    public function testRangeThrowsExceptionIfToIndexPrecedesFromIndex(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->range(3, 2);
    }

    public function testRangeThrowsExceptionIfToIndexIsBeyondCountAfterRemovals(): void
    {
        $this->list->removeRange(3); // [A, B, C]
        $this->expectException(OutOfBoundsException::class);
        $this->list->range(0, 4);
    }
    #endregion

    #region Functional
    public function testFilter(): void
    {
        $list = new ArrayList(range(1, 10));
        $filter = $list->filter(fn($i) => $i % 2 === 0);
        $this->assertSame(5, $filter->count());
        $this->assertSame([2, 4, 6, 8, 10], $filter->toArray());
    }

    public function testFilterReturnsEmptyList(): void
    {
        $filter = $this->list->filter(fn() => false);
        $this->assertTrue($filter->isEmpty());
    }

    public function testFilterDoesNotModifyTheOriginalList(): void
    {
        $filter = $this->list->filter(fn($c) => $c !== 'C');
        $this->assertSame(['A', 'B', 'D', 'E'], $filter->toArray());
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testFilterIgnoresRemovedItems(): void
    {
        $this->list->removeRange(3);
        $filter = $this->list->filter(fn() => true);
        $this->assertSame(['A', 'B', 'C'], $filter->toArray());
    }

    public function testMap(): void
    {
        $list = $this->list->map(fn($c) => ord($c));
        $this->assertSame(5, $list->count());
        $this->assertSame([ord('A'), ord('B'), ord('C'), ord('D'), ord('E')], $list->toArray());
    }

    public function testMapOnEmptyList(): void
    {
        $this->assertTrue((new ArrayList())->map(fn($x) => $x)->isEmpty());
    }

    public function testMapIgnoresRemovedItems(): void
    {
        $this->list->removeRange(3);
        $mapped = $this->list->map(fn($c) => $c . $c);
        $this->assertSame(['AA', 'BB', 'CC'], $mapped->toArray());
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

    public function testForEachOnEmptyList(): void
    {
        $calls = 0;
        (new ArrayList())->forEach(function () use (&$calls): void {
            $calls++;
        });
        $this->assertSame(0, $calls);
    }

    public function testForEachThrowsExceptionIfCollectionIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);
        $this->list->forEach(fn($x) => $this->list->add('Not possible!'));
    }

    public function testAllReturnsTrue(): void
    {
        $this->assertTrue($this->list->all(fn($c) => $c >= 'A' && $c <= 'Z'));
    }

    public function testAllReturnsFalse(): void
    {
        $this->assertFalse($this->list->all(fn($c) => $c === 'A'));
    }

    public function testAllOnEmptyListReturnsTrue(): void
    {
        $this->assertTrue((new ArrayList())->all(fn() => false));
    }

    public function testAllIgnoresRemovedItems(): void
    {
        $this->list->removeRange(3);
        $this->assertTrue($this->list->all(fn($c) => $c !== null));
    }

    public function testAnyReturnsTrue(): void
    {
        $this->assertTrue($this->list->any(fn($c) => $c === 'D'));
    }

    public function testAnyReturnsFalse(): void
    {
        $this->assertFalse($this->list->any(fn($c) => $c >= '0' && $c <= '9'));
    }

    public function testAnyOnEmptyListReturnsFalse(): void
    {
        $this->assertFalse((new ArrayList())->any(fn() => true));
    }

    public function testAnyIgnoresRemovedItems(): void
    {
        $this->list->removeRange(3);
        $this->assertFalse($this->list->any(fn($c) => $c === null || $c === 'D'));
    }
    #endregion

    #region Sort
    public function testSortWithoutComparator(): void
    {
        $shuffled = str_shuffle('ABCDEFG');
        $characters = str_split($shuffled);

        $list = new ArrayList($characters);
        $list->sort();

        $this->assertSame(['A', 'B', 'C', 'D', 'E', 'F', 'G'], $list->toArray());
    }

    public function testSortWithComparator(): void
    {
        $shuffled = str_shuffle('ABCDEFG');
        $characters = str_split($shuffled);

        $list = new ArrayList($characters);
        $list->sort(fn($a, $b) => $b <=> $a); // Sort in descending order

        $this->assertSame(['G', 'F', 'E', 'D', 'C', 'B', 'A'], $list->toArray());
    }

    public function testSortEmptyAndSingleItemList(): void
    {
        $empty = new ArrayList();
        $empty->sort();
        $this->assertTrue($empty->isEmpty());

        $single = new ArrayList(['X']);
        $single->sort();
        $this->assertSame(['X'], $single->toArray());
    }

    public function testSortAfterRemoveAtDoesNotContainStaleItems(): void
    {
        $list = new ArrayList(['E', 'D', 'C', 'B', 'A']);
        $list->removeAt(4);
        $list->removeAt(3); // [E, D, C]
        $list->sort();

        $this->assertSame(3, $list->count());
        $this->assertSame(['C', 'D', 'E'], $list->toArray());
        $this->assertSame(['C', 'D', 'E'], iterator_to_array($list));
    }

    public function testSortWithComparatorAfterRemoveIfDoesNotContainStaleItems(): void
    {
        $list = new ArrayList([5, 1, 4, 2, 3]);
        $list->removeIf(fn($i) => $i > 3); // [1, 2, 3]
        $list->sort(fn($a, $b) => $b <=> $a);

        $this->assertSame(3, $list->count());
        $this->assertSame([3, 2, 1], $list->toArray());
    }

    public function testSortAfterRemovalsKeepsListUsable(): void
    {
        $list = new ArrayList(['D', 'C', 'B', 'A']);
        $list->removeLast(); // [D, C, B]
        $list->sort();       // [B, C, D]
        $list->add('A');
        $list->insert(0, 'Z');

        $this->assertSame(['Z', 'B', 'C', 'D', 'A'], $list->toArray());
    }

    public function testSortRangeWithoutComparator(): void
    {
        $list = new ArrayList(['a', 'b', 'c', 3, 2, 1, 'x', 'y', 'z']);
        $list->sortRange(3, 3);

        $this->assertSame(['a', 'b', 'c', 1, 2, 3, 'x', 'y', 'z'], $list->toArray());
    }

    public function testSortRangeWithComparator(): void
    {
        $list = new ArrayList(['a', 'b', 'c', 3, 2, 1, 'x', 'y', 'z']);
        $list->sortRange(0, 3, fn($a, $b) => $b <=> $a); // descending order
        $list->sortRange(6, 3, fn($a, $b) => $b <=> $a);

        $this->assertSame(['c', 'b', 'a', 3, 2, 1, 'z', 'y', 'x'], $list->toArray());
    }

    public function testSortRangeSortStart(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(0, 3);

        $this->assertSame(['X', 'Y', 'Z', 'B', 'C', 'A', 'D'], $list->toArray());
    }

    public function testSortRangeSortBetween(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(2, 3);

        $this->assertSame(['Z', 'X', 'B', 'C', 'Y', 'A', 'D'], $list->toArray());
    }

    public function testSortRangeSortLast(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(4, 3);

        $this->assertSame(['Z', 'X', 'Y', 'B', 'A', 'C', 'D'], $list->toArray());
    }

    public function testSortRangePassingListCount(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(0, $list->count());

        $this->assertSame(['A', 'B', 'C', 'D', 'X', 'Y', 'Z'], $list->toArray());
    }

    public function testSortRangeWithoutCountSortsRemainingItems(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(3);

        $this->assertSame(['Z', 'X', 'Y', 'A', 'B', 'C', 'D'], $list->toArray());
    }

    public function testSortRangeClampsCountLargerThanRemainingItems(): void
    {
        $list = new ArrayList(['Z', 'X', 'Y', 'B', 'C', 'A', 'D']);
        $list->sortRange(4, 100);

        $this->assertSame(7, $list->count());
        $this->assertSame(['Z', 'X', 'Y', 'B', 'A', 'C', 'D'], $list->toArray());
    }

    public function testSortRangeWithCountLessThanTwoDoesNothing(): void
    {
        $list = new ArrayList(['C', 'B', 'A']);
        $list->sortRange(0, 1);
        $list->sortRange(0, 0);
        $list->sortRange(0, -1);

        $this->assertSame(['C', 'B', 'A'], $list->toArray());
    }

    public function testSortRangeAfterRemovalsDoesNotContainStaleItems(): void
    {
        $list = new ArrayList(['D', 'C', 'B', 'A', 'E']);
        $list->removeLast(); // [D, C, B, A]
        $list->sortRange(1);

        $this->assertSame(4, $list->count());
        $this->assertSame(['D', 'A', 'B', 'C'], $list->toArray());
    }

    public function testSortRangeThrowsExceptionIfIndexIsOutOfBounds(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->sortRange(5, 1);
    }

    public function testSortRangeThrowsExceptionIfIndexIsNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->list->sortRange(-1, 2);
    }
    #endregion

    #region toArray
    public function testToArray(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());
    }

    public function testToArrayOnEmptyList(): void
    {
        $this->assertSame([], (new ArrayList())->toArray());
    }

    public function testToArrayReturnsAnIndependentCopy(): void
    {
        $array = $this->list->toArray();
        $array[0] = 'X';
        $array[] = 'Y';

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $this->list->toArray());

        // Modifying the list afterwards does not affect a previously returned array either
        $array = $this->list->toArray();
        $this->list->set(0, 'Z');
        $this->assertSame('A', $array[0]);
    }

    public function testToArrayAfterRemovalsDoesNotContainStaleItems(): void
    {
        $this->list->removeAt(0);      // [B, C, D, E]
        $this->assertSame(['B', 'C', 'D', 'E'], $this->list->toArray());

        $this->list->removeLast();     // [B, C, D]
        $this->assertSame(['B', 'C', 'D'], $this->list->toArray());

        $this->list->removeIf(fn($c) => $c === 'C'); // [B, D]
        $this->assertSame(['B', 'D'], $this->list->toArray());

        $this->list->removeRange(1);   // [B]
        $this->assertSame(['B'], $this->list->toArray());
    }
    #endregion
}
