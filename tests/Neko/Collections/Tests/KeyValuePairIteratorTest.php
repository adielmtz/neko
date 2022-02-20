<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\Dictionary;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class KeyValuePairIteratorTest extends TestCase
{
    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $dictionary = new Dictionary();
        $dictionary->set('A', 100);
        $dictionary->set('B', 200);
        $dictionary->set('C', 300);

        foreach ($dictionary as $key => $value) {
            if ($value % 2 === 0) {
                $dictionary->set($key, $value * 3);
            }
        }
    }
}
