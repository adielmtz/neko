<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use InvalidArgumentException;
use Neko\Collections\Dictionary;
use Neko\Collections\KeyNotFoundException;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;
use stdClass;
use function fclose;
use function fopen;
use function function_exists;
use function is_resource;
use const M_PI;

final class DictionaryTest extends TestCase
{
    private Dictionary $dictionary;

    public function setUp(): void
    {
        if (!function_exists('spl_object_hash')) {
            $this->markTestSkipped('Dictionary class requires spl_object_hash() function which is not available for this PHP installation');
        }

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

    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);

        foreach ($this->dictionary as $key => $value) {
            if ($value === 'c') {
                $this->dictionary->set($key, 'C');
            }
        }
    }

    public function testEmpty(): void
    {
        $this->dictionary->clear();
        $this->assertTrue($this->dictionary->isEmpty());
        $this->assertSame(0, $this->dictionary->count());
    }

    public function testContainsKey(): void
    {
        $this->assertTrue($this->dictionary->containsKey('C'));
        $this->assertFalse($this->dictionary->containsKey('NOPE'));
    }

    public function testContainsValue(): void
    {
        $this->assertTrue($this->dictionary->containsValue('thirty'));
        $this->assertFalse($this->dictionary->containsValue('seventy'));
    }

    public function testAdd(): void
    {
        $dictionary = new Dictionary();
        $dictionary->add('Key', 'Value');
        $this->assertFalse($dictionary->isEmpty());
        $this->assertSame(1, $dictionary->count());
    }

    public function testAddThrowsExceptionIfKeyExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->add(10, 'ten');
    }

    public function testGet(): void
    {
        $value = $this->dictionary->get(30);
        $this->assertSame('thirty', $value);
    }

    public function testGetThrowsExceptionIfKeyIsNotFound(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->dictionary->get('Unknown Key');
    }

    public function testSet(): void
    {
        $this->dictionary->set('Key', 'Value');
        $this->assertSame('Value', $this->dictionary->get('Key'));

        // Overwrite existing value
        $this->dictionary->set(10, 'nine + one');
        $this->assertSame('nine + one', $this->dictionary->get(10));
    }

    public function testSetIntAsKey(): void
    {
        $this->dictionary->set(1000, 'One Thousand');
        $this->assertSame('One Thousand', $this->dictionary->get(1000));
    }

    public function testSetFloatAsKey(): void
    {
        $this->dictionary->set(M_PI, 'PI');
        $this->assertSame('PI', $this->dictionary->get(M_PI));
    }

    public function testSetBoolAsKey(): void
    {
        $this->dictionary->set(true, 'Watame is best sheep');
        $this->assertSame('Watame is best sheep', $this->dictionary->get(true));
    }

    public function testSetObjectAsKey(): void
    {
        $obj = new stdClass();
        $this->dictionary->set($obj, 'Object as key');
        $this->assertSame('Object as key', $this->dictionary->get($obj));
    }

    public function testSetThrowsExceptionWithNullAsKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->set(null, 0);
    }

    public function testSetThrowsExceptionWithArrayAsKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dictionary->set([], 0);
    }

    public function testSetThrowsExceptionWithResourceAsKey(): void
    {
        try {
            $this->expectException(InvalidArgumentException::class);
            $res = fopen('php://memory', 'w+b');
            $this->dictionary->set($res, 0);
        } finally {
            if (isset($res) && is_resource($res)) {
                fclose($res);
            }
        }
    }

    public function testRemove(): void
    {
        $this->assertTrue($this->dictionary->remove('B'));
        $this->assertFalse($this->dictionary->remove('UNKNOWN'));
    }

    public function testEnsureThatRemoveWork(): void
    {
        $this->expectException(KeyNotFoundException::class);
        $this->dictionary->remove('C');
        $this->dictionary->get('C');
    }

    public function testRemoveDecrementsLength(): void
    {
        $dictionary = new Dictionary();
        $dictionary->set('Key', 'Value');
        $this->assertSame(1, $dictionary->count());

        $dictionary->remove('Key');
        $this->assertSame(0, $dictionary->count());
    }
}
