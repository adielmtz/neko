<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\InvalidOperationException;
use Neko\IO\FileNotFoundException;
use Neko\IO\FileStream;
use Neko\IO\IOException;
use PHPUnit\Framework\TestCase;
use function strlen;
use function unlink;
use const PHP_EOL;

final class FileStreamTest extends TestCase
{
    private static FileStream $persistentStream;
    private FileStream $disposableStream;

    #region Test Environment SetUp
    public static function setUpBeforeClass(): void
    {
        self::$persistentStream = new FileStream('./fst-sample-static-file.txt', 'w+b');
    }

    public static function tearDownAfterClass(): void
    {
        self::$persistentStream->close();
        unlink(self::$persistentStream->getFileName());
    }

    public function setUp(): void
    {
        $this->disposableStream = new FileStream('./fst-sample-file.txt', 'w+b');
    }

    public function tearDown(): void
    {
        $this->disposableStream->close();
        unlink($this->disposableStream->getFileName());
    }

    #endregion

    public function testBasic(): void
    {
        $this->assertTrue($this->disposableStream->canRead());
        $this->assertTrue($this->disposableStream->canWrite());
        $this->assertTrue($this->disposableStream->canSeek());
    }

    public function testFileStreamThrowsFileNotFoundExceptionWhenOpenModeIsRAndTheFileDoesNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);
        $stream = new FileStream('unknown-filename-foo-bar.txt', 'r+b');
    }

    public function testFileStreamThrowsIOExceptionWhenOpenModeIsXAndTheFileAlreadyExist(): void
    {
        $this->expectException(IOException::class);
        $stream = new FileStream(__FILE__, 'x+b');
    }

    public function testBasicStreamClosed(): void
    {
        $this->disposableStream->close();
        $this->assertFalse($this->disposableStream->canRead());
        $this->assertFalse($this->disposableStream->canWrite());
        $this->assertFalse($this->disposableStream->canSeek());
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
        $expected = strlen($text);

        $bytes = self::$persistentStream->write($text);
        $this->assertSame($expected, $bytes);
        $this->assertSame($expected, self::$persistentStream->getSize());
    }

    public function testWriteThrowsExceptionWhenTheStreamIsClosed(): void
    {
        $this->expectException(InvalidOperationException::class);
        $this->disposableStream->close();
        $this->disposableStream->write('Cannot be done!');
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
        $this->disposableStream->read($output, 100);
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
