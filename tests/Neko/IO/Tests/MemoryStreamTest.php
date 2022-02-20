<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\InvalidOperationException;
use Neko\IO\MemoryStream;
use PHPUnit\Framework\TestCase;
use function strlen;
use const PHP_EOL;

final class MemoryStreamTest extends TestCase
{
    public function testBasic(): void
    {
        $stream = new MemoryStream();
        $this->assertTrue($stream->canRead());
        $this->assertTrue($stream->canWrite());
        $this->assertTrue($stream->canSeek());
        $this->assertFalse($stream->endOfStream());
    }

    public function testBasicStreamClosed(): void
    {
        $stream = new MemoryStream();
        $stream->close();
        $this->assertFalse($stream->canRead());
        $this->assertFalse($stream->canWrite());
        $this->assertFalse($stream->canSeek());
    }

    public function testEndOfStreamThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stream = new MemoryStream();
        $stream->close();
        $stream->endOfStream();
    }

    public function testWrite(): MemoryStream
    {
        $text = __FILE__;
        $expected = strlen($text);

        $stream = new MemoryStream();
        $writtenBytes = $stream->write($text);
        $this->assertSame($expected, $writtenBytes);
        $this->assertSame($expected, $stream->getSize());
        return $stream;
    }

    public function testWriteThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stream = new MemoryStream();
        $stream->close();
        $stream->write('Cannot be done!');
    }

    /**
     * @depends testWrite
     */
    public function testRead(MemoryStream $stream): void
    {
        $stream->setPosition(0);
        $data = $stream->read(4096);
        $this->assertSame(__FILE__, $data);
    }

    public function testReadThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $stream = new MemoryStream();
        $stream->close();
        $stream->read(100);
    }

    public function testWriteLine(): MemoryStream
    {
        $text = __FILE__;
        $expected = strlen($text) + strlen(PHP_EOL);

        $stream = new MemoryStream();
        $writtenBytes = $stream->writeLine($text);
        $this->assertSame($expected, $writtenBytes);
        $this->assertSame($expected, $stream->getSize());
        return $stream;
    }

    /**
     * @depends testWriteLine
     */
    public function testReadLine(MemoryStream $stream): void
    {
        $stream->setPosition(0);
        $data = $stream->read(4096);
        $this->assertSame(__FILE__ . PHP_EOL, $data);
    }

    /**
     * @depends testWrite
     */
    public function testCopyTo(MemoryStream $sourceStream): void
    {
        $sourceStream->setPosition(0);

        $destinationStream = new MemoryStream();
        $sourceStream->copyTo($destinationStream);

        $this->assertSame($sourceStream->getSize(), $destinationStream->getSize());
        $this->assertSame($sourceStream->readToEnd(), $destinationStream->readToEnd());
    }
}
