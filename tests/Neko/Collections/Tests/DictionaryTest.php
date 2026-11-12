<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use InvalidArgumentException;
use Neko\Collections\ArrayList;
use Neko\Collections\ConcurrentMutationException;
use Neko\Collections\Dictionary;
use Neko\Collections\KeyNotFoundException;
use Neko\Collections\KeyValuePair;
use Neko\Collections\Map;
use Neko\NotSupportedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;
use function array_key_first;
use function fclose;
use function fopen;
use function serialize;
use function spl_object_id;
use function unserialize;
use const INF;
use const M_PI;

final class DictionaryTest extends TestCase
{
    private Dictionary $dictionary;

    public function setUp(): void
    {
        $this->dictionary = new Dictionary();
        $this->dictionary->set('A', 'a');
        $this->dictionary->set('B', 'b');
        $this->dictionary->set('C', 'c');
        $this->dictionary->set('D', 'd');
        $this->dictionary->set('E', 'e');

        $this->dictionary->set(10, 'ten');
        $this->dictionary->set(20, 'twenty');
        $this->dictionary->set(30, 'thirty');
        $this->dictionary->set(40, 'forty');
        $this->dictionary->set(50, 'fifty');
    }

    public static function validKeyProvider(): array
    {
        return [
            'int'          => [1000],
            'negative int' => [-1],
            'zero'         => [0],
            'string'       => ['Key'],
            'empty string' => [''],
            'float'        => [M_PI],
            'infinity'     => [INF],
            'true'         => [true],
            'false'        => [false],
            'null'         => [null],
            'object'       => [new stdClass()],
        ];
    }

    public static function invalidKeyProvider(): array
    {
        return [
            'empty array' => [[]],
            'array'       => [[1, 2, 3]],
        ];
    }

    #region Constructor
    public function testImplementsMap(): void
    {
        $this->assertInstanceOf(Map::class, $this->dictionary);
    }

    public function testConstructorWithoutArgument(): void
    {
        $dictionary = new Dictionary();
        $this->assertTrue($dictionary->isEmpty());
        $this->assertSame(0, $dictionary->count());
    }

    public function testConstructorWithNullArgument(): void
    {
        $dictionary = new Dictionary(null);
        $this->assertTrue($dictionary->isEmpty());
    }

    public function testConstructorWithArrayArgument(): void
    {
        $dictionary = new Dictionary(['A', 'B', 'C']);
        $this->assertSame(3, $dictionary->count());
        $this->assertSame('A', $dictionary->get(0));
        $this->assertSame('B', $dictionary->get(1));
        $this->assertSame('C', $dictionary->get(2));
    }

    public function testConstructorWithAssociativeArrayArgument(): void
    {
        $dictionary = new Dictionary([20 => 'twenty', 'key' => 'value']);
        $this->assertSame(2, $dictionary->count());
        $this->assertSame('twenty', $dictionary->get(20));
        $this->assertSame('value', $dictionary->get('key'));
        $this->assertSame([20, 'key'], $dictionary->getKeys());
    }

    public function testConstructorWithDictionaryArgument(): void
    {
        $key = new stdClass();
        $argument = new Dictionary();
        $argument->set('sample', 'value');
        $argument->set($key, 'object');

        $dictionary = new Dictionary($argument);
        $dictionary->set('hello', 'world');

        $this->assertSame(3, $dictionary->count());
        $this->assertSame('value', $dictionary->get('sample'));
        $this->assertSame('object', $dictionary->get($key));
        $this->assertSame('world', $dictionary->get('hello'));
        $this->assertSame(2, $argument->count());
    }

    public function testConstructorWithIterableArgument(): void
    {
        $argument = new ArrayList(['A', 'B', 'C', 'D']);
        $dictionary = new Dictionary($argument);

        $this->assertSame(4, $dictionary->count());
        $this->assertSame('A', $dictionary->get(0));
        $this->assertSame('B', $dictionary->get(1));
        $this->assertSame('C', $dictionary->get(2));
        $this->assertSame('D', $dictionary->get(3));
    }

    public function testConstructorWithGeneratorArgumentKeepsNonScalarKeys(): void
    {
        $key = new stdClass();
        $generator = (function () use ($key) {
            yield $key => 'object';
            yield 1.5 => 'float';
            yield null => 'null';
            yield 'x' => 'first';
            yield 'x' => 'second';
        })();

        $dictionary = new Dictionary($generator);
        $this->assertSame(4, $dictionary->count());
        $this->assertSame('object', $dictionary->get($key));
        $this->assertSame('float', $dictionary->get(1.5));
        $this->assertSame('null', $dictionary->get(null));
        $this->assertSame('second', $dictionary->get('x'));
    }

    public function testConstructorThrowsExceptionWithInvalidKey(): void
    {
        $generator = (function () {
            yield [1] => 'array';
        })();

        $this->expectException(InvalidArgumentException::class);
        new Dictionary($generator);
    }
    #endregion

    #region Clone and serialization
    public function testCloneIsIndependentFromOriginal(): void
    {
        $clone = clone $this->dictionary;
        $clone->set('A', 'changed');
        $clone->set('Z', 'z');
        $clone->removeKey(10);

        $this->assertSame('a', $this->dictionary->get('A'));
        $this->assertFalse($this->dictionary->containsKey('Z'));
        $this->assertTrue($this->dictionary->containsKey(10));
        $this->assertSame(10, $this->dictionary->count());

        $this->assertSame('changed', $clone->get('A'));
        $this->assertSame(10, $clone->count());
    }

    public function testOriginalChangesDoNotAffectClone(): void
    {
        $clone = clone $this->dictionary;
        $this->dictionary->set('B', 'changed');
        $this->assertSame('b', $clone->get('B'));
    }

    public function testCloneKeepsObjectKeysAndOrder(): void
    {
        $key = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set('first', 1);
        $dictionary->set($key, 2);

        $clone = clone $dictionary;
        $this->assertSame(['first', $key], $clone->getKeys());
        $this->assertSame(2, $clone->get($key));
    }

    public function testCloneCanBeIteratedAndModifiedAfterIteration(): void
    {
        $clone = clone $this->dictionary;
        $keys = [];
        foreach ($clone as $key => $value) {
            $keys[] = $key;
        }

        $clone->set('F', 'f');
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 10, 20, 30, 40, 50], $keys);
        $this->assertSame(11, $clone->count());
    }

    public function testSerialize(): string
    {
        $dictionary = new Dictionary();
        $dictionary->set('foo', 'bar');
        $dictionary->set(10, 'ten');
        $dictionary->set('10', 10);
        $dictionary->set('array', []);
        $dictionary->set(1.5, 'float');
        $dictionary->set(true, 'bool');
        $dictionary->set(null, 'null');

        $serialized = serialize($dictionary);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerialize')]
    public function testUnserialize(string $serialized): Dictionary
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Dictionary::class, $restored);
        $this->assertSame(7, $restored->count());
        return $restored;
    }

    #[Depends('testUnserialize')]
    public function testUnserializedDictionaryKeepsKeyValuePairs(Dictionary $restored): void
    {
        $this->assertSame('bar', $restored->get('foo'));
        $this->assertSame('ten', $restored->get(10));
        $this->assertSame(10, $restored->get('10'));
        $this->assertSame([], $restored->get('array'));
        $this->assertSame('float', $restored->get(1.5));
        $this->assertSame('bool', $restored->get(true));
        $this->assertSame('null', $restored->get(null));
        $this->assertSame(['foo', 10, '10', 'array', 1.5, true, null], $restored->getKeys());
    }

    public function testSerializeEmptyDictionary(): string
    {
        $empty = new Dictionary();
        $serialized = serialize($empty);
        $this->assertNotEmpty($serialized);
        return $serialized;
    }

    #[Depends('testSerializeEmptyDictionary')]
    public function testUnserializeEmptyDictionary(string $serialized): void
    {
        $restored = unserialize($serialized);
        $this->assertInstanceOf(Dictionary::class, $restored);
        $this->assertSame(0, $restored->count());
        $this->assertTrue($restored->isEmpty());
    }

    public function testUnserializedDictionaryKeepsObjectKeys(): void
    {
        $key = new stdClass();
        $key->name = 'MyKey';
        $dictionary = new Dictionary();
        $dictionary->set($key, 'MyValue');
        // Serialize the key with the dictionary, so the restored key is the same instance in both places
        $payload = [$dictionary, $key];

        [$restored, $restoredKey] = unserialize(serialize($payload));
        $this->assertInstanceOf(Dictionary::class, $restored);
        $this->assertNotSame($key, $restoredKey);
        $this->assertSame('MyKey', $restoredKey->name);
        $this->assertTrue($restored->containsKey($restoredKey));
        $this->assertSame('MyValue', $restored->get($restoredKey));
        $this->assertSame([$restoredKey], $restored->getKeys());
        $this->assertFalse($restored->containsKey($key));
    }

    public function testUnserializedDictionaryCanBeModified(): void
    {
        $restored = unserialize(serialize($this->dictionary));
        $this->assertInstanceOf(Dictionary::class, $restored);
        $restored->set('F', 'f');
        $this->assertTrue($restored->removeKey('A'));
        $this->assertSame(10, $restored->count());
        $this->assertSame('f', $restored->get('F'));
    }
    #endregion

    #region Iteration
    public function testIteratorYieldsKeysAndValuesInInsertionOrder(): void
    {
        $keys = [];
        $values = [];
        foreach ($this->dictionary as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->assertSame(['A', 'B', 'C', 'D', 'E', 10, 20, 30, 40, 50], $keys);
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'ten', 'twenty', 'thirty', 'forty', 'fifty'], $values);
    }

    public function testIteratorYieldsNonScalarKeys(): void
    {
        $object = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set($object, 'object');
        $dictionary->set(null, 'null');
        $dictionary->set(1.5, 'float');
        $dictionary->set(false, 'bool');

        $keys = [];
        foreach ($dictionary as $key => $value) {
            $keys[] = $key;
        }

        $this->assertSame([$object, null, 1.5, false], $keys);
    }

    public function testIteratorOverEmptyDictionary(): void
    {
        $count = 0;
        foreach (new Dictionary() as $value) {
            $count++;
        }

        $this->assertSame(0, $count);
    }

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->dictionary as $key => $value) {
            if ($value === 'c') {
                $this->dictionary->set($key, 'C');
            }
        }
    }

    public function testIteratorThrowsExceptionIfAKeyIsRemoved(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->dictionary as $key => $value) {
            $this->dictionary->removeKey($key);
        }
    }

    public function testIteratorThrowsExceptionIfModifiedOnLastEntry(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->dictionary as $key => $value) {
            if ($key === 50) {
                $this->dictionary->set('F', 'f');
            }
        }
    }

    public function testIteratorThrowsExceptionIfCleared(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->dictionary as $value) {
            $this->dictionary->clear();
        }
    }

    public function testIteratorThrowsExceptionIfModifiedThroughArrayAccess(): void
    {
        $this->expectException(ConcurrentMutationException::class);

        foreach ($this->dictionary as $key => $value) {
            unset($this->dictionary[$key]);
        }
    }

    public function testIteratorDoesNotThrowWhenFailedOperationsAreCalled(): void
    {
        $visited = 0;
        foreach ($this->dictionary as $value) {
            $this->dictionary->removeKey('UNKNOWN');
            $this->dictionary->remove(new KeyValuePair('A', 'wrong'));
            $visited++;
        }

        $this->assertSame(10, $visited);
    }
    #endregion

    #region isEmpty, count, clear
    public function testIsEmpty(): void
    {
        $this->assertFalse($this->dictionary->isEmpty());
        $this->assertTrue((new Dictionary())->isEmpty());
    }

    public function testCount(): void
    {
        $this->assertSame(10, $this->dictionary->count());
        $this->assertCount(10, $this->dictionary);
    }

    public function testClear(): void
    {
        $this->dictionary->clear();
        $this->assertTrue($this->dictionary->isEmpty());
        $this->assertSame(0, $this->dictionary->count());
        $this->assertSame([], $this->dictionary->getKeys());
        $this->assertFalse($this->dictionary->containsKey('A'));
    }

    public function testClearEmptyDictionary(): void
    {
        $dictionary = new Dictionary();
        $dictionary->clear();
        $this->assertTrue($dictionary->isEmpty());
    }

    public function testDictionaryCanBeReusedAfterClear(): void
    {
        $this->dictionary->clear();
        $this->dictionary->set('A', 'new');
        $this->assertSame(1, $this->dictionary->count());
        $this->assertSame('new', $this->dictionary->get('A'));
    }
    #endregion

    #region contains
    public function testContainsReturnsTrueWithSimilarKVP(): void
    {
        $this->assertTrue($this->dictionary->contains(new KeyValuePair('C', 'c')));
        $this->assertTrue($this->dictionary->contains(new KeyValuePair(30, 'thirty')));
    }

    public function testContainsReturnsTrueWithOwnEntry(): void
    {
        $entries = $this->dictionary->toArray();
        $this->assertTrue($this->dictionary->contains($entries[0]));
    }

    public function testContainsReturnsFalseWithDifferentValue(): void
    {
        $this->assertFalse($this->dictionary->contains(new KeyValuePair('C', 'F')));
    }

    public function testContainsReturnsFalseWithUnknownKey(): void
    {
        $this->assertFalse($this->dictionary->contains(new KeyValuePair('Z', 'c')));
    }

    public function testContainsComparesValuesStrictly(): void
    {
        $dictionary = new Dictionary(['one' => 1, 'object' => new stdClass()]);
        $this->assertTrue($dictionary->contains(new KeyValuePair('one', 1)));
        $this->assertFalse($dictionary->contains(new KeyValuePair('one', '1')));
        $this->assertFalse($dictionary->contains(new KeyValuePair('one', 1.0)));
        $this->assertFalse($dictionary->contains(new KeyValuePair('one', true)));
        $this->assertFalse($dictionary->contains(new KeyValuePair('object', new stdClass())));
    }

    public function testContainsComparesKeysStrictly(): void
    {
        $this->assertFalse($this->dictionary->contains(new KeyValuePair('10', 'ten')));
        $this->assertFalse($this->dictionary->contains(new KeyValuePair(10.0, 'ten')));
    }

    public function testContainsWithNullValue(): void
    {
        $dictionary = new Dictionary(['key' => null]);
        $this->assertTrue($dictionary->contains(new KeyValuePair('key')));
        $this->assertFalse($dictionary->contains(new KeyValuePair('other')));
    }

    public function testContainsReturnsFalseWithNonKeyValuePair(): void
    {
        $this->assertFalse($this->dictionary->contains('a'));
        $this->assertFalse($this->dictionary->contains('A'));
        $this->assertFalse($this->dictionary->contains(null));
        $this->assertFalse($this->dictionary->contains(['A', 'a']));
        $this->assertFalse($this->dictionary->contains(new stdClass()));
    }

    public function testContainsThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->contains(new KeyValuePair([1], 'a'));
    }
    #endregion

    #region containsKey, containsValue
    public function testContainsKey(): void
    {
        $this->assertTrue($this->dictionary->containsKey('C'));
        $this->assertTrue($this->dictionary->containsKey(30));
        $this->assertFalse($this->dictionary->containsKey('NOPE'));
        $this->assertFalse($this->dictionary->containsKey('c'));
    }

    public function testContainsKeyReturnsTrueForNullValue(): void
    {
        $this->dictionary->set('null', null);
        $this->assertTrue($this->dictionary->containsKey('null'));
    }

    #[DataProvider('validKeyProvider')]
    public function testContainsKeyWithValidKeyTypes(mixed $key): void
    {
        $dictionary = new Dictionary();
        $this->assertFalse($dictionary->containsKey($key));
        $dictionary->set($key, 'value');
        $this->assertTrue($dictionary->containsKey($key));
    }

    #[DataProvider('invalidKeyProvider')]
    public function testContainsKeyThrowsExceptionWithInvalidKey(mixed $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->containsKey($key);
    }

    public function testContainsValue(): void
    {
        $this->assertTrue($this->dictionary->containsValue('thirty'));
        $this->assertFalse($this->dictionary->containsValue('seventy'));
        $this->assertFalse($this->dictionary->containsValue('THIRTY'));
    }

    public function testContainsValueComparesStrictly(): void
    {
        $object = new stdClass();
        $dictionary = new Dictionary(['int' => 1, 'null' => null, 'object' => $object]);

        $this->assertTrue($dictionary->containsValue(1));
        $this->assertFalse($dictionary->containsValue('1'));
        $this->assertFalse($dictionary->containsValue(true));
        $this->assertTrue($dictionary->containsValue(null));
        $this->assertFalse($dictionary->containsValue(false));
        $this->assertTrue($dictionary->containsValue($object));
        $this->assertFalse($dictionary->containsValue(new stdClass()));
    }

    public function testContainsValueOnEmptyDictionary(): void
    {
        $this->assertFalse((new Dictionary())->containsValue(null));
    }
    #endregion

    #region getKeys, getValues
    public function testGetKeys(): void
    {
        $this->assertSame(['A', 'B', 'C', 'D', 'E', 10, 20, 30, 40, 50], $this->dictionary->getKeys());
    }

    public function testGetValues(): void
    {
        $this->assertSame(
            ['a', 'b', 'c', 'd', 'e', 'ten', 'twenty', 'thirty', 'forty', 'fifty'],
            $this->dictionary->getValues(),
        );
    }

    public function testGetKeysAndValuesOnEmptyDictionary(): void
    {
        $dictionary = new Dictionary();
        $this->assertSame([], $dictionary->getKeys());
        $this->assertSame([], $dictionary->getValues());
    }

    public function testGetKeysReturnsOriginalKeyTypes(): void
    {
        $object = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set('10', 'string');
        $dictionary->set(10, 'int');
        $dictionary->set(1.0, 'float');
        $dictionary->set(true, 'bool');
        $dictionary->set(null, 'null');
        $dictionary->set($object, 'object');

        $this->assertSame(['10', 10, 1.0, true, null, $object], $dictionary->getKeys());
        $this->assertSame(['string', 'int', 'float', 'bool', 'null', 'object'], $dictionary->getValues());
    }

    public function testGetKeysAndValuesKeepOrderAfterRemoval(): void
    {
        $this->dictionary->removeKey('C');
        $this->dictionary->removeKey(10);
        $this->dictionary->set('C', 'new');

        $this->assertSame(['A', 'B', 'D', 'E', 20, 30, 40, 50, 'C'], $this->dictionary->getKeys());
        $this->assertSame(['a', 'b', 'd', 'e', 'twenty', 'thirty', 'forty', 'fifty', 'new'], $this->dictionary->getValues());
    }
    #endregion

    #region get
    public function testGet(): void
    {
        $this->assertSame('thirty', $this->dictionary->get(30));
        $this->assertSame('c', $this->dictionary->get('C'));
    }

    public function testGetReturnsNullValue(): void
    {
        $this->dictionary->set('null', null);
        $this->assertNull($this->dictionary->get('null'));
    }

    public function testGetThrowsExceptionIfKeyIsNotFound(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->dictionary->get('Unknown Key');
    }

    public function testGetExceptionMessageDescribesStringKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessageIs("Key 'zz' does not exist in the dictionary");
        $this->dictionary->get('zz');
    }

    public function testGetExceptionMessageDescribesIntKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessageIs('Key 99 does not exist in the dictionary');
        $this->dictionary->get(99);
    }

    public function testGetExceptionMessageDescribesObjectKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessageIs('Key of type stdClass does not exist in the dictionary');
        $this->dictionary->get(new stdClass());
    }

    public function testGetExceptionMessageDescribesNullKey(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessageIs('Key NULL does not exist in the dictionary');
        $this->dictionary->get(null);
    }

    #[DataProvider('invalidKeyProvider')]
    public function testGetThrowsExceptionWithInvalidKey(mixed $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->get($key);
    }

    public function testGetThrowsExceptionWithArrayKeyMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs("Value of type 'array' is not a valid key");
        $this->dictionary->get([]);
    }
    #endregion

    #region set
    public function testSet(): void
    {
        $this->dictionary->set('Key', 'Value');
        $this->assertSame('Value', $this->dictionary->get('Key'));
        $this->assertSame(11, $this->dictionary->count());
    }

    public function testSetReplacesExistingValue(): void
    {
        $this->dictionary->set(10, 'nine + one');
        $this->assertSame('nine + one', $this->dictionary->get(10));
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testSetKeepsOriginalPositionOnReplace(): void
    {
        $this->dictionary->set('A', 'first');
        $this->dictionary->set(50, 'last');

        $this->assertSame(['A', 'B', 'C', 'D', 'E', 10, 20, 30, 40, 50], $this->dictionary->getKeys());
        $this->assertSame('first', $this->dictionary->getValues()[0]);
        $this->assertSame('last', $this->dictionary->getValues()[9]);
    }

    public function testSetKeepsOriginalKeyOnReplace(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set(0.0, 'positive');
        $dictionary->set(-0.0, 'negative');

        $this->assertSame(1, $dictionary->count());
        $this->assertSame('negative', $dictionary->get(0.0));
        // The key first added is kept: (string) -0.0 would be '-0'
        $this->assertSame('0', (string) $dictionary->getKeys()[0]);
    }

    #[DataProvider('validKeyProvider')]
    public function testSetAndGetWithValidKeyTypes(mixed $key): void
    {
        $this->dictionary->set($key, 'value');
        $this->assertSame('value', $this->dictionary->get($key));
        $this->assertSame(11, $this->dictionary->count());
    }

    public function testSetNullAsKey(): void
    {
        $this->dictionary->set(null, 'Null key');
        $this->assertSame('Null key', $this->dictionary->get(null));
        $this->assertTrue($this->dictionary->containsKey(null));
    }

    #[DataProvider('invalidKeyProvider')]
    public function testSetThrowsExceptionWithInvalidKey(mixed $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->set($key, 0);
    }

    public function testSetThrowsExceptionWithResourceAsKey(): void
    {
        $res = fopen('php://memory', 'w+b');
        try {
            $this->expectException(InvalidArgumentException::class);
            $this->dictionary->set($res, 0);
        } finally {
            fclose($res);
        }
    }

    public function testSetWithInvalidKeyDoesNotModifyDictionary(): void
    {
        try {
            $this->dictionary->set([], 0);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame(10, $this->dictionary->count());
    }
    #endregion

    #region Key equality
    public function testFloatKeysAreNotRounded(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set(0.1 + 0.2, 'sum');
        $dictionary->set(0.3, 'literal');

        $this->assertSame(2, $dictionary->count());
        $this->assertSame('sum', $dictionary->get(0.1 + 0.2));
        $this->assertSame('literal', $dictionary->get(0.3));
    }

    public function testPositiveAndNegativeZeroAreTheSameKey(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set(0.0, 'zero');

        $this->assertTrue($dictionary->containsKey(-0.0));
        $this->assertSame('zero', $dictionary->get(-0.0));

        $dictionary->set(-0.0, 'negative zero');
        $this->assertSame(1, $dictionary->count());
        $this->assertSame('negative zero', $dictionary->get(0.0));
    }

    public function testFloatIntStringAndBoolAreDifferentKeys(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set(1.0, 'float');
        $dictionary->set(1, 'int');
        $dictionary->set('1', 'string');
        $dictionary->set(true, 'bool');

        $this->assertSame(4, $dictionary->count());
        $this->assertSame('float', $dictionary->get(1.0));
        $this->assertSame('int', $dictionary->get(1));
        $this->assertSame('string', $dictionary->get('1'));
        $this->assertSame('bool', $dictionary->get(true));
    }

    public function testFalsyKeysAreDifferentKeys(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set(0, 'int');
        $dictionary->set(0.0, 'float');
        $dictionary->set('0', 'string');
        $dictionary->set('', 'empty string');
        $dictionary->set(false, 'bool');
        $dictionary->set(null, 'null');

        $this->assertSame(6, $dictionary->count());
        $this->assertSame('int', $dictionary->get(0));
        $this->assertSame('float', $dictionary->get(0.0));
        $this->assertSame('string', $dictionary->get('0'));
        $this->assertSame('empty string', $dictionary->get(''));
        $this->assertSame('bool', $dictionary->get(false));
        $this->assertSame('null', $dictionary->get(null));
    }

    public function testIntAndNumericStringAreDifferentKeys(): void
    {
        $this->assertTrue($this->dictionary->containsKey(10));
        $this->assertFalse($this->dictionary->containsKey('10'));

        $this->dictionary->set('10', 'string ten');
        $this->assertSame(11, $this->dictionary->count());
        $this->assertSame('ten', $this->dictionary->get(10));
        $this->assertSame('string ten', $this->dictionary->get('10'));
    }

    public function testStringsThatLookLikeEncodedKeysDoNotCollide(): void
    {
        $object = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set('x', 'plain');
        $dictionary->set($object, 'object');
        $dictionary->set(null, 'null');
        $dictionary->set(true, 'bool');
        $dictionary->set(1.5, 'float');

        foreach (['s:x', 'o:' . spl_object_id($object), 'n:', 'b:1', 'd:1.5', 'd:1.5000000000000000'] as $key) {
            $this->assertFalse($dictionary->containsKey($key), "String key '$key' must not collide");
        }

        $dictionary->set('s:x', 'encoded string');
        $dictionary->set('o:' . spl_object_id($object), 'encoded object');
        $dictionary->set('n:', 'encoded null');
        $dictionary->set('b:1', 'encoded bool');

        $this->assertSame(9, $dictionary->count());
        $this->assertSame('plain', $dictionary->get('x'));
        $this->assertSame('object', $dictionary->get($object));
        $this->assertSame('null', $dictionary->get(null));
        $this->assertSame('bool', $dictionary->get(true));
        $this->assertSame('encoded string', $dictionary->get('s:x'));
    }

    public function testObjectKeysAreIdentityBased(): void
    {
        $first = new stdClass();
        $second = new stdClass();

        $dictionary = new Dictionary();
        $dictionary->set($first, 'first');
        $dictionary->set($second, 'second');

        $this->assertSame(2, $dictionary->count());
        $this->assertSame('first', $dictionary->get($first));
        $this->assertSame('second', $dictionary->get($second));
        $this->assertFalse($dictionary->containsKey(new stdClass()));
    }

    public function testMutatedObjectKeyIsStillFound(): void
    {
        $key = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set($key, 'value');
        $key->changed = true;

        $this->assertSame('value', $dictionary->get($key));
    }

    public function testKeyValuePairCanBeUsedAsKey(): void
    {
        $key = new KeyValuePair('k', 'v');
        $dictionary = new Dictionary();
        $dictionary->set($key, 'value');

        $this->assertSame('value', $dictionary->get($key));
        $this->assertFalse($dictionary->containsKey(new KeyValuePair('k', 'v')));
    }
    #endregion

    #region add
    public function testAdd(): void
    {
        $dictionary = new Dictionary();
        $dictionary->add(new KeyValuePair('Key', 'Value'));
        $this->assertFalse($dictionary->isEmpty());
        $this->assertSame(1, $dictionary->count());
        $this->assertSame('Value', $dictionary->get('Key'));
    }

    public function testAddAppendsAtTheEnd(): void
    {
        $this->dictionary->add(new KeyValuePair('F', 'f'));
        $this->assertSame('F', $this->dictionary->getKeys()[10]);
    }

    public function testAddWithDefaultNullValue(): void
    {
        $dictionary = new Dictionary();
        $dictionary->add(new KeyValuePair('Key'));
        $this->assertTrue($dictionary->containsKey('Key'));
        $this->assertNull($dictionary->get('Key'));
    }

    public function testAddWithObjectAndNullKeys(): void
    {
        $object = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->add(new KeyValuePair($object, 'object'));
        $dictionary->add(new KeyValuePair(null, 'null'));

        $this->assertSame('object', $dictionary->get($object));
        $this->assertSame('null', $dictionary->get(null));
    }

    public function testAddStoresACopy(): void
    {
        $pair = new KeyValuePair('Key', 'Value');
        $dictionary = new Dictionary();
        $dictionary->add($pair);

        $pair->value = 'Changed';
        $this->assertSame('Value', $dictionary->get('Key'));
    }

    public function testAddThrowsExceptionIfKeyExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->add(new KeyValuePair(10, 'ten'));
    }

    public function testAddExceptionMessageDescribesKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs("Key 'A' already exists in the dictionary");
        $this->dictionary->add(new KeyValuePair('A', 'other'));
    }

    public function testAddDoesNotReplaceExistingValue(): void
    {
        try {
            $this->dictionary->add(new KeyValuePair('A', 'other'));
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame('a', $this->dictionary->get('A'));
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testAddThrowsExceptionIfKeyExistsWithNullValue(): void
    {
        $this->dictionary->set('null', null);
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->add(new KeyValuePair('null', 'value'));
    }

    public function testAddThrowsTypeErrorWithNonKeyValuePair(): void
    {
        $this->expectException(TypeError::class);
        $this->dictionary->add('Key');
    }

    public function testAddThrowsTypeErrorWithArray(): void
    {
        $this->expectException(TypeError::class);
        $this->dictionary->add(['Key' => 'Value']);
    }

    public function testAddThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->add(new KeyValuePair([], 'Value'));
    }
    #endregion

    #region remove
    public function testRemove(): void
    {
        $this->assertTrue($this->dictionary->remove(new KeyValuePair('B', 'b')));
        $this->assertFalse($this->dictionary->containsKey('B'));
        $this->assertSame(9, $this->dictionary->count());
    }

    public function testRemoveReturnsFalseWithUnknownKey(): void
    {
        $this->assertFalse($this->dictionary->remove(new KeyValuePair('UNKNOWN', 'b')));
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testRemoveReturnsFalseWithDifferentValue(): void
    {
        $this->assertFalse($this->dictionary->remove(new KeyValuePair('B', 'B')));
        $this->assertFalse($this->dictionary->remove(new KeyValuePair(10, 10)));
        $this->assertTrue($this->dictionary->containsKey('B'));
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testRemoveComparesValuesStrictly(): void
    {
        $dictionary = new Dictionary(['one' => 1]);
        $this->assertFalse($dictionary->remove(new KeyValuePair('one', '1')));
        $this->assertTrue($dictionary->remove(new KeyValuePair('one', 1)));
        $this->assertTrue($dictionary->isEmpty());
    }

    public function testRemoveReturnsFalseWithNonKeyValuePair(): void
    {
        $this->assertFalse($this->dictionary->remove('B'));
        $this->assertFalse($this->dictionary->remove('b'));
        $this->assertFalse($this->dictionary->remove(null));
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testRemoveWithOwnEntry(): void
    {
        $entries = $this->dictionary->toArray();
        $this->assertTrue($this->dictionary->remove($entries[0]));
        $this->assertFalse($this->dictionary->containsKey('A'));
    }

    public function testRemoveThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->remove(new KeyValuePair([], 'a'));
    }

    public function testEnsureThatRemoveWork(): void
    {
        $this->dictionary->remove(new KeyValuePair('C', 'c'));
        $this->expectException(KeyNotFoundException::class);
        $this->dictionary->get('C');
    }
    #endregion

    #region removeKey
    public function testRemoveKey(): void
    {
        $this->assertTrue($this->dictionary->removeKey('B'));
        $this->assertFalse($this->dictionary->containsKey('B'));
        $this->assertFalse($this->dictionary->removeKey('B'));
        $this->assertFalse($this->dictionary->removeKey('UNKNOWN'));
    }

    public function testRemoveKeyDecrementsCount(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set('Key', 'Value');
        $this->assertSame(1, $dictionary->count());

        $dictionary->removeKey('Key');
        $this->assertSame(0, $dictionary->count());
        $this->assertTrue($dictionary->isEmpty());
    }

    public function testRemoveKeyComparesKeysStrictly(): void
    {
        $this->assertFalse($this->dictionary->removeKey('10'));
        $this->assertFalse($this->dictionary->removeKey(10.0));
        $this->assertTrue($this->dictionary->removeKey(10));
    }

    public function testRemoveKeyWithNullValue(): void
    {
        $this->dictionary->set('null', null);
        $this->assertTrue($this->dictionary->removeKey('null'));
        $this->assertFalse($this->dictionary->containsKey('null'));
    }

    public function testRemoveKeyWithObjectKey(): void
    {
        $key = new stdClass();
        $this->dictionary->set($key, 'object');
        $this->assertFalse($this->dictionary->removeKey(new stdClass()));
        $this->assertTrue($this->dictionary->removeKey($key));
        $this->assertSame(10, $this->dictionary->count());
    }

    #[DataProvider('invalidKeyProvider')]
    public function testRemoveKeyThrowsExceptionWithInvalidKey(mixed $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->removeKey($key);
    }
    #endregion

    #region toArray
    public function testToArray(): void
    {
        $array = $this->dictionary->toArray();
        $this->assertCount(10, $array);
        $this->assertContainsOnlyInstancesOf(KeyValuePair::class, $array);
        $this->assertSame(0, array_key_first($array));

        $this->assertSame('A', $array[0]->key);
        $this->assertSame('a', $array[0]->value);
        $this->assertSame(50, $array[9]->key);
        $this->assertSame('fifty', $array[9]->value);
    }

    public function testToArrayOnEmptyDictionary(): void
    {
        $this->assertSame([], (new Dictionary())->toArray());
    }

    public function testToArrayReturnsCopies(): void
    {
        $array = $this->dictionary->toArray();
        $array[0]->value = 'changed';

        $this->assertSame('a', $this->dictionary->get('A'));
        $this->assertNotSame($array[0], $this->dictionary->toArray()[0]);
    }

    public function testToArrayKeepsObjectKeyIdentity(): void
    {
        $key = new stdClass();
        $dictionary = new Dictionary();
        $dictionary->set($key, 'value');

        $this->assertSame($key, $dictionary->toArray()[0]->key);
    }
    #endregion

    #region ArrayAccess
    public function testOffsetGet(): void
    {
        $this->assertSame('a', $this->dictionary['A']);
        $this->assertSame('ten', $this->dictionary[10]);
    }

    public function testOffsetGetThrowsExceptionIfKeyIsNotFound(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessageIs("Key 'zz' does not exist in the dictionary");
        $unused = $this->dictionary['zz'];
    }

    public function testOffsetGetWithObjectKey(): void
    {
        $key = new stdClass();
        $this->dictionary->set($key, 'object');
        $this->assertSame('object', $this->dictionary[$key]);
    }

    public function testOffsetSet(): void
    {
        $this->dictionary['F'] = 'f';
        $this->dictionary['A'] = 'changed';

        $this->assertSame('f', $this->dictionary->get('F'));
        $this->assertSame('changed', $this->dictionary->get('A'));
        $this->assertSame(11, $this->dictionary->count());
        $this->assertSame('A', $this->dictionary->getKeys()[0]);
    }

    public function testOffsetSetWithNonScalarKeys(): void
    {
        $key = new stdClass();
        $this->dictionary[$key] = 'object';
        $this->dictionary[1.5] = 'float';
        $this->dictionary[true] = 'bool';

        $this->assertSame('object', $this->dictionary->get($key));
        $this->assertSame('float', $this->dictionary->get(1.5));
        $this->assertSame('bool', $this->dictionary->get(true));
        $this->assertFalse($this->dictionary->containsKey(1));
    }

    public function testOffsetSetThrowsExceptionWhenAppending(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->dictionary[] = 'value';
    }

    public function testOffsetSetThrowsExceptionWithNullKey(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->dictionary[null] = 'value';
    }

    public function testOffsetSetWithNullKeyDoesNotModifyDictionary(): void
    {
        try {
            $this->dictionary[] = 'value';
            $this->fail('Expected NotSupportedException');
        } catch (NotSupportedException) {
        }

        $this->assertSame(10, $this->dictionary->count());
        $this->assertFalse($this->dictionary->containsKey(null));
    }

    public function testOffsetSetThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary[[1]] = 'value';
    }

    public function testOffsetExists(): void
    {
        $this->assertTrue(isset($this->dictionary['A']));
        $this->assertTrue(isset($this->dictionary[10]));
        $this->assertFalse(isset($this->dictionary['Z']));
        $this->assertFalse(isset($this->dictionary['10']));
    }

    public function testOffsetExistsReturnsFalseForNullValue(): void
    {
        $this->dictionary->set('null', null);
        $this->assertFalse(isset($this->dictionary['null']));
        $this->assertTrue($this->dictionary->containsKey('null'));
    }

    public function testOffsetExistsReturnsTrueForFalsyValues(): void
    {
        $dictionary = new Dictionary(['zero' => 0, 'false' => false, 'empty' => '']);
        $this->assertTrue(isset($dictionary['zero']));
        $this->assertTrue(isset($dictionary['false']));
        $this->assertTrue(isset($dictionary['empty']));
    }

    public function testOffsetExistsWithNullKey(): void
    {
        $this->assertFalse(isset($this->dictionary[null]));
        $this->dictionary->set(null, 'value');
        $this->assertTrue(isset($this->dictionary[null]));
    }

    public function testOffsetExistsThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $unused = isset($this->dictionary[[1]]);
    }

    public function testEmptyOnOffset(): void
    {
        $dictionary = new Dictionary(['zero' => 0, 'null' => null, 'value' => 'v', 'empty' => '']);
        $this->assertTrue(empty($dictionary['zero']));
        $this->assertTrue(empty($dictionary['null']));
        $this->assertTrue(empty($dictionary['empty']));
        $this->assertTrue(empty($dictionary['missing']));
        $this->assertFalse(empty($dictionary['value']));
    }

    public function testNullCoalescingOnOffset(): void
    {
        $this->assertSame('a', $this->dictionary['A'] ?? 'default');
        $this->assertSame('default', $this->dictionary['missing'] ?? 'default');
    }

    public function testOffsetUnset(): void
    {
        unset($this->dictionary['A']);
        $this->assertFalse($this->dictionary->containsKey('A'));
        $this->assertSame(9, $this->dictionary->count());
    }

    public function testOffsetUnsetWithUnknownKey(): void
    {
        unset($this->dictionary['UNKNOWN']);
        $this->assertSame(10, $this->dictionary->count());
    }

    public function testOffsetUnsetWithNullKey(): void
    {
        $this->dictionary->set(null, 'value');
        unset($this->dictionary[null]);
        $this->assertFalse($this->dictionary->containsKey(null));
    }

    public function testOffsetUnsetThrowsExceptionWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        unset($this->dictionary[[1]]);
    }
    #endregion
}
