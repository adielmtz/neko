<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\KeyValuePair;
use PHPUnit\Framework\TestCase;
use stdClass;

final class KeyValuePairTest extends TestCase
{
    public function testConstructorSetsKeyAndValue(): void
    {
        $pair = new KeyValuePair('key', 'val');
        $this->assertSame('key', $pair->key);
        $this->assertSame('val', $pair->value);
    }

    public function testConstructorAcceptsObjectKey(): void
    {
        $key = new stdClass();
        $pair = new KeyValuePair($key, 1);
        $this->assertSame($key, $pair->key);
        $this->assertSame(1, $pair->value);
    }

    public function testValueDefaultsToNull(): void
    {
        $pair = new KeyValuePair('key');
        $this->assertSame('key', $pair->key);
        $this->assertNull($pair->value);
    }

    public function testValueIsMutable(): void
    {
        $pair = new KeyValuePair('key', 'old');
        $pair->value = 'new';
        $this->assertSame('new', $pair->value);
    }
}
