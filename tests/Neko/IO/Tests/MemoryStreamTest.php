<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\InvalidOperationException;
use Neko\IO\MemoryStream;
use Neko\NotSupportedException;
use PHPUnit\Framework\TestCase;
use function serialize;
use function strlen;
use const PHP_EOL;

final class MemoryStreamTest extends TestCase
{
    private static MemoryStream $persistentStream;
    private MemoryStream $disposableStream;

    #region Test Environment SetUp
    public static function setUpBeforeClass(): void
    {
        self::$persistentStream = new MemoryStream();
    }

    public static function tearDownAfterClass(): void
    {
        self::$persistentStream->close();
    }

    public function setUp(): void
    {
        $this->disposableStream = new MemoryStream();
    }

    public function tearDown(): void
    {
        $this->disposableStream->close();
    }

    #endregion

    public function testBasic(): void
    {
        $this->assertTrue($this->disposableStream->canRead());
        $this->assertTrue($this->disposableStream->canWrite());
        $this->assertTrue($this->disposableStream->canSeek());
        $this->assertFalse($this->disposableStream->endOfStream());
    }

    public function testBasicStreamClosed(): void
    {
        $this->disposableStream->close();
        $this->assertFalse($this->disposableStream->canRead());
        $this->assertFalse($this->disposableStream->canWrite());
        $this->assertFalse($this->disposableStream->canSeek());
    }

    public function testMemoryStreamThrowsNotSupportedExceptionWhenTryingToSerialize(): void
    {
        $this->expectException(NotSupportedException::class);
        serialize($this->disposableStream);
    }

    public function testEndOfStreamThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->disposableStream->close();
        $this->disposableStream->endOfStream();
    }

    public function testWrite(): void
    {
        $text = __FILE__;
        $expectedBytes = strlen($text);

        $writtenBytes = self::$persistentStream->write($text);
        $this->assertSame($expectedBytes, $writtenBytes);
        $this->assertSame($expectedBytes, self::$persistentStream->getSize());
    }

    public function testWriteThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->disposableStream->close();
        $this->disposableStream->write('This should not be allowed!');
    }

    public function testRead(): void
    {
        self::$persistentStream->setPosition(0);
        self::$persistentStream->read($output, 4096);
        $this->assertSame(__FILE__, $output);
    }

    public function testReadReturnsEmptyStringOnEndOfStream(): void
    {
        $bytesRead = $this->disposableStream->read($output, 4096);
        $this->assertSame(0, $bytesRead);
        $this->assertSame('', $output);
    }

    public function testReadThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->disposableStream->close();
        $this->disposableStream->read($_, 520);
    }

    public function testTruncate(): void
    {
        self::$persistentStream->setSize(0);
        $this->assertSame(0, self::$persistentStream->getSize());
    }

    /**
     * @depends testTruncate
     */
    public function testWriteLine(): void
    {
        $text = __FILE__;
        $expectedBytes = strlen($text) + strlen(PHP_EOL);

        $writtenBytes = self::$persistentStream->writeLine($text);
        $this->assertSame($expectedBytes, $writtenBytes);
        $this->assertSame($expectedBytes, self::$persistentStream->getSize());
    }

    /**
     * @depends testWriteLine
     */
    public function testReadLine(): void
    {
        self::$persistentStream->setPosition(0);
        self::$persistentStream->read($output, 4096);
        $this->assertSame(__FILE__ . PHP_EOL, $output);
    }

    /**
     * @depends testWriteLine
     */
    public function testCopyTo(): void
    {
        self::$persistentStream->setPosition(0);
        self::$persistentStream->copyTo($this->disposableStream);

        $this->assertSame(self::$persistentStream->getSize(), $this->disposableStream->getSize());

        self::$persistentStream->setPosition(0);
        $this->disposableStream->setPosition(0);

        $this->assertSame(self::$persistentStream->readToEnd(), $this->disposableStream->readToEnd());
    }
}
