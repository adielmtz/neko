<?php declare(strict_types=1);
namespace Neko\Collections\Tests;

use Neko\Collections\ArrayList;
use Neko\InvalidOperationException;
use PHPUnit\Framework\TestCase;

final class IndexedListIteratorTest extends TestCase
{
    public function testIteratorThrowsExceptionIfTheCollectionIsModified(): void
    {
        $this->expectException(InvalidOperationException::class);
        $list = new ArrayList();
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
