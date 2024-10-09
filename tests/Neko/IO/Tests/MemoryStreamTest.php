<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\InvalidOperationException;
use Neko\IO\MemoryStream;
use Neko\NotSupportedException;
use PHPUnit\Framework\Attributes\Depends;
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
        $this->assertTrue($this->disposableStream->isReadable());
        $this->assertTrue($this->disposableStream->isWritable());
        $this->assertTrue($this->disposableStream->isSeekable());
        $this->assertFalse($this->disposableStream->endOfStream());
    }

    public function testBasicStreamClosed(): void
    {
        $this->disposableStream->close();
        $this->assertFalse($this->disposableStream->isReadable());
        $this->assertFalse($this->disposableStream->isWritable());
        $this->assertFalse($this->disposableStream->isSeekable());
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
        $output = self::$persistentStream->read(4096);
        $this->assertSame(__FILE__, $output);
    }

    public function testReadReturnsEmptyStringOnEndOfStream(): void
    {
        $output = $this->disposableStream->read(4096);
        $this->assertSame(0, strlen($output));
        $this->assertSame('', $output);
    }

    public function testReadThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->disposableStream->close();
        $this->disposableStream->read(520);
    }

    public function testTruncate(): void
    {
        self::$persistentStream->setSize(0);
        $this->assertSame(0, self::$persistentStream->getSize());
    }

    #[Depends('testTruncate')]
    public function testWriteLine(): void
    {
        $text = __FILE__;
        $expectedBytes = strlen($text) + strlen(PHP_EOL);

        $writtenBytes = self::$persistentStream->writeLine($text);
        $this->assertSame($expectedBytes, $writtenBytes);
        $this->assertSame($expectedBytes, self::$persistentStream->getSize());
    }

    #[Depends('testWriteLine')]
    public function testReadLine(): void
    {
        self::$persistentStream->setPosition(0);
        $output = self::$persistentStream->read(4096);
        $this->assertSame(__FILE__ . PHP_EOL, $output);
    }

    #[Depends('testWriteLine')]
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
