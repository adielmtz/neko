<?php declare(strict_types=1);
namespace Neko\IO\OS\Windows\Tests;

use Neko\Environment;
use Neko\IO\OS\Windows\PathWindows;
use PHPUnit\Framework\TestCase;

final class PathWindowsTest extends TestCase
{
    private PathWindows $path;

    protected function setUp(): void
    {
        $this->path = new PathWindows();
    }

    public function testIsDirectorySeparator(): void
    {
        $this->assertTrue($this->path->isDirectorySeparator('\\'));
        $this->assertTrue($this->path->isDirectorySeparator('/'));
        $this->assertFalse($this->path->isDirectorySeparator(''));
        $this->assertFalse($this->path->isDirectorySeparator(':'));
        $this->assertFalse($this->path->isDirectorySeparator('\\\\'));
    }

    public function testEndsInDirectorySeparator(): void
    {
        $this->assertTrue($this->path->endsInDirectorySeparator('C:\Windows\\'));
        $this->assertTrue($this->path->endsInDirectorySeparator('C:/Windows/'));
        $this->assertFalse($this->path->endsInDirectorySeparator('C:\Windows'));
        $this->assertFalse($this->path->endsInDirectorySeparator('C:'));
        $this->assertFalse($this->path->endsInDirectorySeparator(''));
    }

    public function testIsPathRooted(): void
    {
        $this->assertTrue($this->path->isPathRooted('C:\Windows'));
        $this->assertTrue($this->path->isPathRooted('c:/Windows'));
        $this->assertTrue($this->path->isPathRooted('C:Windows'));
        $this->assertTrue($this->path->isPathRooted('C:'));
        $this->assertTrue($this->path->isPathRooted('\foo\bar'));
        $this->assertTrue($this->path->isPathRooted('/foo/bar'));
        $this->assertTrue($this->path->isPathRooted('\\\\server\share'));
        $this->assertTrue($this->path->isPathRooted('\\\\?\C:\foo'));
        $this->assertFalse($this->path->isPathRooted('foo\bar'));
        $this->assertFalse($this->path->isPathRooted('desktop.ini'));
        $this->assertFalse($this->path->isPathRooted('1:\foo'));
        $this->assertFalse($this->path->isPathRooted(''));
    }

    public function testIsPathFullyQualified(): void
    {
        $this->assertTrue($this->path->isPathFullyQualified('C:\Windows'));
        $this->assertTrue($this->path->isPathFullyQualified('C:/'));
        $this->assertTrue($this->path->isPathFullyQualified('\\\\server\share\foo'));
        $this->assertTrue($this->path->isPathFullyQualified('//server/share'));
        $this->assertTrue($this->path->isPathFullyQualified('\\\\?\C:\foo'));
        $this->assertFalse($this->path->isPathFullyQualified('C:Windows'));
        $this->assertFalse($this->path->isPathFullyQualified('C:'));
        $this->assertFalse($this->path->isPathFullyQualified('\foo'));
        $this->assertFalse($this->path->isPathFullyQualified('\\'));
        $this->assertFalse($this->path->isPathFullyQualified('foo'));
        $this->assertFalse($this->path->isPathFullyQualified(''));
    }

    public function testGetPathRoot(): void
    {
        $this->assertSame('C:\\', $this->path->getPathRoot('C:\Windows'));
        $this->assertSame('C:/', $this->path->getPathRoot('C:/Windows'));
        $this->assertSame('C:', $this->path->getPathRoot('C:Windows'));
        $this->assertSame('C:', $this->path->getPathRoot('C:'));
        $this->assertSame('\\', $this->path->getPathRoot('\foo\bar'));
        $this->assertSame('/', $this->path->getPathRoot('/foo/bar'));
        $this->assertSame('\\\\server\share', $this->path->getPathRoot('\\\\server\share\foo\bar'));
        $this->assertSame('\\\\server\share', $this->path->getPathRoot('\\\\server\share'));
        $this->assertSame('//server/share', $this->path->getPathRoot('//server/share/foo'));
        $this->assertSame('\\\\server', $this->path->getPathRoot('\\\\server'));
        $this->assertSame('\\\\?\C:\\', $this->path->getPathRoot('\\\\?\C:\foo'));
        $this->assertSame('\\\\.\COM1', $this->path->getPathRoot('\\\\.\COM1'));
        $this->assertSame('\\\\?\UNC\server\share', $this->path->getPathRoot('\\\\?\UNC\server\share\foo'));
        $this->assertSame('', $this->path->getPathRoot('foo\bar'));
        $this->assertSame('', $this->path->getPathRoot(''));
    }

    public function testExtensions(): void
    {
        $this->assertSame('.txt', $this->path->getExtension('C:\foo\bar.txt'));
        $this->assertSame('', $this->path->getExtension('C:\foo.d\bar'));
        $this->assertSame('', $this->path->getExtension('C:/foo.d/bar'));
        $this->assertTrue($this->path->hasExtension('C:\foo\bar.txt'));
        $this->assertFalse($this->path->hasExtension('C:\foo.d\bar'));
        $this->assertSame('C:\foo.d\bar.txt', $this->path->changeExtension('C:\foo.d\bar', 'txt'));
        $this->assertSame('C:\foo\bar', $this->path->changeExtension('C:\foo\bar.txt', null));
    }

    public function testGetFileName(): void
    {
        $this->assertSame('bar.txt', $this->path->getFileName('C:\foo\bar.txt'));
        $this->assertSame('bar.txt', $this->path->getFileName('C:/foo/bar.txt'));
        $this->assertSame('bar.txt', $this->path->getFileName('C:bar.txt'));
        $this->assertSame('bar.txt', $this->path->getFileName('C:\bar.txt'));
        $this->assertSame('', $this->path->getFileName('C:\foo\\'));
        $this->assertSame('', $this->path->getFileName('C:\\'));
        $this->assertSame('', $this->path->getFileName('C:'));
        $this->assertSame('', $this->path->getFileName('\\\\server\share'));
        $this->assertSame('file', $this->path->getFileName('\\\\server\share\file'));
        $this->assertSame('bar', $this->path->getFileNameWithoutExtension('C:\foo\bar.txt'));
    }

    public function testGetDirectoryName(): void
    {
        $this->assertSame('C:\foo', $this->path->getDirectoryName('C:\foo\bar.txt'));
        $this->assertSame('C:/foo', $this->path->getDirectoryName('C:/foo/bar.txt'));
        $this->assertSame('C:\foo', $this->path->getDirectoryName('C:\foo\\\\bar'));
        $this->assertSame('C:\\', $this->path->getDirectoryName('C:\foo'));
        $this->assertSame('C:', $this->path->getDirectoryName('C:foo'));
        $this->assertSame('\\', $this->path->getDirectoryName('\foo'));
        $this->assertSame('\\\\server\share', $this->path->getDirectoryName('\\\\server\share\foo'));
        $this->assertSame('foo', $this->path->getDirectoryName('foo\bar'));
        $this->assertSame('', $this->path->getDirectoryName('C:\\'));
        $this->assertSame('', $this->path->getDirectoryName('C:'));
        $this->assertSame('', $this->path->getDirectoryName('\\\\server\share'));
        $this->assertSame('', $this->path->getDirectoryName('foo'));
    }

    public function testGetFullPath(): void
    {
        $this->assertSame('C:\foo\baz', $this->path->getFullPath('C:\foo\bar\..\baz'));
        $this->assertSame('C:\foo\baz', $this->path->getFullPath('C:/foo/./bar/../baz'));
        $this->assertSame('C:\foo\\', $this->path->getFullPath('C:\foo\bar\..\\'));
        $this->assertSame('C:\foo', $this->path->getFullPath('C:\foo\\\\\\bar\..'));
        $this->assertSame('C:\foo', $this->path->getFullPath('C:\..\..\foo'));
        $this->assertSame('C:\\', $this->path->getFullPath('C:/'));
        $this->assertSame('\\\\server\share\b', $this->path->getFullPath('//server/share/a/../b'));
        $this->assertSame('\\\\server\share\b', $this->path->getFullPath('\\\\server\share\..\b'));
        $this->assertSame('\\\\?\C:\foo\..\bar', $this->path->getFullPath('\\\\?\C:\foo\..\bar'));
    }

    public function testGetFullPathResolvesPartiallyQualifiedPaths(): void
    {
        if (!Environment::IS_WINDOWS_MACHINE) {
            $this->markTestSkipped('The current directory is not a Windows path');
        }

        $cwd = Environment::getCurrentDirectory();
        $drive = $cwd[0];
        $otherDrive = $drive === 'Z' ? 'Y' : 'Z';

        $this->assertSame($cwd . '\foo\bar', $this->path->getFullPath('foo/bar'));
        $this->assertSame($cwd, $this->path->getFullPath('.'));
        $this->assertSame($drive . ':\foo', $this->path->getFullPath('\foo'));
        $this->assertSame($cwd . '\foo', $this->path->getFullPath($drive . ':foo'));
        $this->assertSame($otherDrive . ':\foo', $this->path->getFullPath($otherDrive . ':foo'));
    }

    public function testGetRelativePath(): void
    {
        $this->assertSame('baz', $this->path->getRelativePath('C:\foo\bar', 'C:\foo\bar\baz'));
        $this->assertSame('..\qux', $this->path->getRelativePath('C:/foo/bar', 'C:/foo/qux'));
        $this->assertSame('baz\\', $this->path->getRelativePath('C:\foo\bar', 'C:\foo\bar\baz\\'));
        $this->assertSame('.', $this->path->getRelativePath('C:\foo', 'C:\foo\\'));
        $this->assertSame('baz', $this->path->getRelativePath('c:\FOO\Bar', 'C:\foo\bar\baz')); // case-insensitive
        $this->assertSame('D:\foo', $this->path->getRelativePath('C:\foo', 'D:\foo')); // different roots
        $this->assertSame('..\b', $this->path->getRelativePath('\\\\server\share\a', '\\\\server\share\b'));
        $this->assertSame('\\\\other\share\a', $this->path->getRelativePath('\\\\server\share\a', '\\\\other\share\a'));
    }

    public function testGetRelativePathComparesNonAsciiCharactersCaseInsensitively(): void
    {
        $this->assertSame('x', $this->path->getRelativePath('C:\Café', 'C:\CAFÉ\x'));
        $this->assertSame('x', $this->path->getRelativePath('C:\Ελλάδα', 'C:\ΕΛΛΆΔΑ\x'));
        $this->assertSame('x', $this->path->getRelativePath('C:\Привет', 'C:\пРИВЕТ\x'));
    }

    public function testGetRelativePathUsesSimpleCaseMapping(): void
    {
        // 'ß' only uppercases to 'SS' with full case mapping, which NTFS does not use
        $this->assertSame('..\STRASSE\x', $this->path->getRelativePath('C:\straße', 'C:\STRASSE\x'));
    }

    public function testGetRelativePathDoesNotTreatDifferentInvalidUtf8NamesAsEqual(): void
    {
        $this->assertSame("..\\b\xFE\\x", $this->path->getRelativePath("C:\\a\xFF", "C:\\b\xFE\\x"));
        $this->assertSame("..\\A\xFE\\x", $this->path->getRelativePath("C:\\a\xFF", "C:\\A\xFE\\x"));
        $this->assertSame('x', $this->path->getRelativePath("C:\\a\xFF", "C:\\A\xFF\\x"));
    }

    public function testCombine(): void
    {
        $this->assertSame('', $this->path->combine());
        $this->assertSame('foo\bar', $this->path->combine('foo', 'bar'));
        $this->assertSame('foo/bar', $this->path->combine('foo/', 'bar'));
        $this->assertSame('C:\Windows', $this->path->combine('Users', 'Admin', 'C:', 'Windows'));
        $this->assertSame('C:\Windows\System32', $this->path->combine('Users', 'C:\Windows', 'System32'));
        $this->assertSame('\foo\bar', $this->path->combine('Users', '\foo', 'bar'));
        $this->assertSame('\\\\server\share\foo', $this->path->combine('C:\\', '\\\\server\share', 'foo'));
    }

    public function testJoin(): void
    {
        $this->assertSame('', $this->path->join());
        $this->assertSame('foo\bar', $this->path->join('foo', 'bar'));
        $this->assertSame('Users\Admin\C:\Windows', $this->path->join('Users', 'Admin', 'C:', 'Windows'));
        $this->assertSame('Users\foo\bar', $this->path->join('Users', '\foo', 'bar'));
    }
}
