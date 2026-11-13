<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\Environment;
use Neko\IO\Path;
use PHPUnit\Framework\TestCase;
use const DIRECTORY_SEPARATOR;

/**
 * Tests the platform-specific behavior of the facade.
 * The rules of each platform are tested in PathUnixTest and PathWindowsTest.
 */
final class PathTest extends TestCase
{
    public function testUsesTheRulesOfTheCurrentPlatform(): void
    {
        $this->assertTrue(Path::isDirectorySeparator(DIRECTORY_SEPARATOR));
        $this->assertTrue(Path::isDirectorySeparator('/'));
        $this->assertSame(Environment::IS_WINDOWS_MACHINE, Path::isDirectorySeparator('\\'));
        $this->assertSame(Environment::IS_WINDOWS_MACHINE, Path::isPathRooted('C:\Windows'));
        $this->assertSame('foo' . DIRECTORY_SEPARATOR . 'bar', Path::combine('foo', 'bar'));
        $this->assertSame('foo' . DIRECTORY_SEPARATOR . 'bar', Path::join('foo', 'bar'));
    }

    public function testWorksWithMagicConstants(): void
    {
        $this->assertSame(__DIR__, Path::getDirectoryName(__FILE__));
        $this->assertSame('PathTest.php', Path::getFileName(__FILE__));
        $this->assertSame('PathTest', Path::getFileNameWithoutExtension(__FILE__));
        $this->assertSame('.php', Path::getExtension(__FILE__));
        $this->assertTrue(Path::hasExtension(__FILE__));
        $this->assertTrue(Path::isPathRooted(__FILE__));
        $this->assertTrue(Path::isPathFullyQualified(__FILE__));
        $this->assertSame(__FILE__, Path::getFullPath(__FILE__));
        $this->assertSame(__FILE__, Path::changeExtension(Path::changeExtension(__FILE__, null), 'php'));
        $this->assertSame(Path::getPathRoot(__DIR__), Path::getPathRoot(__FILE__));
        $this->assertSame('PathTest.php', Path::getRelativePath(__DIR__, __FILE__));
    }

    public function testGetFullPathResolvesRelativePathsAgainstTheCurrentDirectory(): void
    {
        $cwd = Environment::getCurrentDirectory();
        $this->assertSame(Path::combine($cwd, 'foo'), Path::getFullPath('foo'));
    }
}
