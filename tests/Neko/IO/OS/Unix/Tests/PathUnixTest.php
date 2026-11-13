<?php declare(strict_types=1);
namespace Neko\IO\OS\Unix\Tests;

use Neko\Environment;
use Neko\IO\OS\Unix\PathUnix;
use PHPUnit\Framework\TestCase;
use const PHP_OS_FAMILY;

final class PathUnixTest extends TestCase
{
    private PathUnix $path;

    protected function setUp(): void
    {
        $this->path = new PathUnix();
    }

    public function testIsDirectorySeparator(): void
    {
        $this->assertTrue($this->path->isDirectorySeparator('/'));
        $this->assertFalse($this->path->isDirectorySeparator('\\'));
        $this->assertFalse($this->path->isDirectorySeparator(''));
        $this->assertFalse($this->path->isDirectorySeparator('|'));
        $this->assertFalse($this->path->isDirectorySeparator('//'));
    }

    public function testEndsInDirectorySeparator(): void
    {
        $this->assertTrue($this->path->endsInDirectorySeparator('/home/user/'));
        $this->assertTrue($this->path->endsInDirectorySeparator('/'));
        $this->assertFalse($this->path->endsInDirectorySeparator('/home/user'));
        $this->assertFalse($this->path->endsInDirectorySeparator('C:\Windows\\'));
        $this->assertFalse($this->path->endsInDirectorySeparator(''));
    }

    public function testIsPathRooted(): void
    {
        $this->assertTrue($this->path->isPathRooted('/'));
        $this->assertTrue($this->path->isPathRooted('/foo/bar'));
        $this->assertFalse($this->path->isPathRooted('foo/bar'));
        $this->assertFalse($this->path->isPathRooted('../foo'));
        $this->assertFalse($this->path->isPathRooted('~/todo.txt'));
        $this->assertFalse($this->path->isPathRooted('C:/Windows'));
        $this->assertFalse($this->path->isPathRooted('\foo'));
        $this->assertFalse($this->path->isPathRooted(''));
    }

    public function testIsPathFullyQualified(): void
    {
        $this->assertTrue($this->path->isPathFullyQualified('/'));
        $this->assertTrue($this->path->isPathFullyQualified('/foo'));
        $this->assertFalse($this->path->isPathFullyQualified('foo'));
        $this->assertFalse($this->path->isPathFullyQualified(''));
    }

    public function testGetPathRoot(): void
    {
        $this->assertSame('/', $this->path->getPathRoot('/foo/bar'));
        $this->assertSame('/', $this->path->getPathRoot('//foo'));
        $this->assertSame('', $this->path->getPathRoot('foo/bar'));
        $this->assertSame('', $this->path->getPathRoot('C:\Windows'));
        $this->assertSame('', $this->path->getPathRoot(''));
    }

    public function testHasExtension(): void
    {
        $this->assertTrue($this->path->hasExtension('.gitignore'));
        $this->assertTrue($this->path->hasExtension('/home/user/.gitignore'));
        $this->assertTrue($this->path->hasExtension('path/to/foo.tar.gz'));
        $this->assertFalse($this->path->hasExtension('/foo/bar.txt/'));
        $this->assertFalse($this->path->hasExtension('/foo/bar.'));
        $this->assertFalse($this->path->hasExtension('/foo.d/bar'));
        $this->assertFalse($this->path->hasExtension(''));
    }

    public function testGetExtension(): void
    {
        $this->assertSame('.gitignore', $this->path->getExtension('.gitignore'));
        $this->assertSame('.gitignore', $this->path->getExtension('/home/user/.gitignore'));
        $this->assertSame('.gz', $this->path->getExtension('path/to/foo.tar.gz'));
        $this->assertSame('', $this->path->getExtension('/foo/bar.txt/'));
        $this->assertSame('', $this->path->getExtension('/foo/bar.'));
        $this->assertSame('', $this->path->getExtension('/foo.d/bar'));
        $this->assertSame('.txt', $this->path->getExtension('/foo.d\bar.txt')); // '\' is not a separator
        $this->assertSame('', $this->path->getExtension(''));
    }

    public function testChangeExtension(): void
    {
        $this->assertSame('/foo/bar/main.c', $this->path->changeExtension('/foo/bar/main.h', '.c'));
        $this->assertSame('main.c', $this->path->changeExtension('main.h', 'c'));
        $this->assertSame('main.c', $this->path->changeExtension('main', 'c'));
        $this->assertSame('../foo.tar.gz', $this->path->changeExtension('../foo.tar.ball', '.gz'));
        $this->assertSame('/home/user/.gitconfig', $this->path->changeExtension('/home/user/.gitignore', '.gitconfig'));
        $this->assertSame('~/movie.mp4', $this->path->changeExtension('~/movie.', '.mp4'));
        $this->assertSame('/foo.d/bar.txt', $this->path->changeExtension('/foo.d/bar', 'txt'));
        $this->assertSame('/foo/bar/baz.', $this->path->changeExtension('/foo/bar/baz.bin', '.'));
        $this->assertSame('/foo/bar/baz.', $this->path->changeExtension('/foo/bar/baz.bin', ''));
        $this->assertSame('', $this->path->changeExtension('', '.txt'));
    }

    public function testChangeExtensionRemovesExtensionIfNullIsPassed(): void
    {
        $this->assertSame('~/todo', $this->path->changeExtension('~/todo.txt', null));
        $this->assertSame('~/', $this->path->changeExtension('~/.gitignore', null));
        $this->assertSame('/foo.d/bar', $this->path->changeExtension('/foo.d/bar', null));
    }

    public function testGetFileName(): void
    {
        $this->assertSame('.gitignore', $this->path->getFileName('/foo/bar/.gitignore'));
        $this->assertSame('File.txt', $this->path->getFileName('File.txt'));
        $this->assertSame('Shopping.txt', $this->path->getFileName('../Shopping.txt'));
        $this->assertSame('bar', $this->path->getFileName('/bar'));
        $this->assertSame('.', $this->path->getFileName('/foo/bar/.'));
        $this->assertSame('.', $this->path->getFileName('.'));
        $this->assertSame('a\b.txt', $this->path->getFileName('/foo/a\b.txt'));
    }

    public function testGetFileNameReturnsEmptyStringIfThereIsNoFileName(): void
    {
        $this->assertSame('', $this->path->getFileName('/foo/bar/'));
        $this->assertSame('', $this->path->getFileName('/'));
        $this->assertSame('', $this->path->getFileName(''));
    }

    public function testGetFileNameWithoutExtension(): void
    {
        $this->assertSame('File', $this->path->getFileNameWithoutExtension('/foo/File.txt'));
        $this->assertSame('foo.tar', $this->path->getFileNameWithoutExtension('foo.tar.gz'));
        $this->assertSame('', $this->path->getFileNameWithoutExtension('.gitignore'));
        $this->assertSame('', $this->path->getFileNameWithoutExtension('/foo/'));
        $this->assertSame('', $this->path->getFileNameWithoutExtension(''));
    }

    public function testGetDirectoryName(): void
    {
        $this->assertSame('/foo/bar/baz', $this->path->getDirectoryName('/foo/bar/baz/abc'));
        $this->assertSame('a/b/c', $this->path->getDirectoryName('a/b/c/d.png'));
        $this->assertSame('..', $this->path->getDirectoryName('../foo.php'));
        $this->assertSame('.', $this->path->getDirectoryName('./bar.php'));
        $this->assertSame('/', $this->path->getDirectoryName('/baz.php'));
        $this->assertSame('/', $this->path->getDirectoryName('//baz.php'));
    }

    public function testGetDirectoryNameTrimsTrailingDirectorySeparators(): void
    {
        $this->assertSame('/foo/bar', $this->path->getDirectoryName('/foo/bar/'));
        $this->assertSame('/foo/bar', $this->path->getDirectoryName('/foo/bar//'));
        $this->assertSame('/foo', $this->path->getDirectoryName('/foo//bar'));
    }

    public function testGetDirectoryNameReturnsEmptyStringIfThereIsNoDirectory(): void
    {
        $this->assertSame('', $this->path->getDirectoryName('FileName-only'));
        $this->assertSame('', $this->path->getDirectoryName('/'));
        $this->assertSame('', $this->path->getDirectoryName(''));
    }

    public function testGetFullPath(): void
    {
        $this->assertSame('/foo/baz', $this->path->getFullPath('/foo/bar/../baz'));
        $this->assertSame('/foo/', $this->path->getFullPath('/foo/bar/../'));
        $this->assertSame('/foo', $this->path->getFullPath('/foo/bar/..'));
        $this->assertSame('/foo/bar', $this->path->getFullPath('/foo/./bar'));
        $this->assertSame('/foo/bar', $this->path->getFullPath('//foo///bar'));
        $this->assertSame('/foo', $this->path->getFullPath('/../../foo'));
        $this->assertSame('/', $this->path->getFullPath('/..'));
        $this->assertSame('/', $this->path->getFullPath('/'));
    }

    public function testGetFullPathResolvesRelativePathsAgainstTheCurrentDirectory(): void
    {
        if (Environment::IS_WINDOWS_MACHINE) {
            $this->markTestSkipped('The current directory is not a Unix path');
        }

        $cwd = Environment::getCurrentDirectory();
        $this->assertSame($cwd . '/foo/bar', $this->path->getFullPath('foo/bar'));
        $this->assertSame($cwd, $this->path->getFullPath('.'));
        $this->assertSame($this->path->getDirectoryName($cwd) ?: '/', $this->path->getFullPath('..'));
    }

    public function testGetRelativePath(): void
    {
        $this->assertSame('baz', $this->path->getRelativePath('/foo/bar', '/foo/bar/baz'));
        $this->assertSame('baz/', $this->path->getRelativePath('/foo/bar', '/foo/bar/baz/'));
        $this->assertSame('..', $this->path->getRelativePath('/foo/bar', '/foo'));
        $this->assertSame('../qux', $this->path->getRelativePath('/foo/bar', '/foo/qux'));
        $this->assertSame('../../a/b', $this->path->getRelativePath('/foo/bar/', '/a/b'));
        $this->assertSame('.', $this->path->getRelativePath('/foo/bar', '/foo/bar/'));
        $this->assertSame('.', $this->path->getRelativePath('/', '/'));
        $this->assertSame('foo', $this->path->getRelativePath('/', '/foo'));
        $this->assertSame('baz', $this->path->getRelativePath('/foo/./bar', '/foo/x/../bar/baz'));
    }

    public function testGetRelativePathIsCaseSensitive(): void
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $this->markTestSkipped('macOS file systems are case-insensitive');
        }

        $this->assertSame('../../FOO/bar/baz', $this->path->getRelativePath('/foo/bar', '/FOO/bar/baz'));
    }

    public function testCombine(): void
    {
        $this->assertSame('', $this->path->combine());
        $this->assertSame('', $this->path->combine(''));
        $this->assertSame('Hello', $this->path->combine('Hello'));
        $this->assertSame('foo/bar', $this->path->combine('foo', 'bar'));
        $this->assertSame('foo/bar', $this->path->combine('foo/', 'bar'));
        $this->assertSame('foo/bar', $this->path->combine('foo', '', 'bar'));
        $this->assertSame('../foo/bar/./baz/file.txt', $this->path->combine('..', 'foo/bar', './baz', 'file.txt'));
    }

    public function testCombineDiscardsPreviousArgumentsIfArgumentIsRooted(): void
    {
        $this->assertSame('/home/user', $this->path->combine('/abc', 'def', '/home', 'user'));
        $this->assertSame('/', $this->path->combine('foo', '/'));
        $this->assertSame('Users/C:/Windows', $this->path->combine('Users', 'C:', 'Windows'));
    }

    public function testJoin(): void
    {
        $this->assertSame('', $this->path->join());
        $this->assertSame('', $this->path->join(''));
        $this->assertSame('Hello', $this->path->join('Hello'));
        $this->assertSame('foo/bar', $this->path->join('foo', 'bar'));
        $this->assertSame('foo/bar', $this->path->join('foo', '', 'bar'));
        $this->assertSame('/abc/def/home/user', $this->path->join('/abc', 'def', '/home', 'user'));
        $this->assertSame('foo//bar', $this->path->join('foo/', '/bar'));
    }
}
