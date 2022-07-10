<?php declare(strict_types=1);
namespace Neko\IO\Tests;

use Neko\Environment;
use Neko\IO\Path;
use PHPUnit\Framework\TestCase;
use const DIRECTORY_SEPARATOR;

final class PathTest extends TestCase
{
    public function testIsDirectorySeparator(): void
    {
        $this->assertTrue(Path::isDirectorySeparator(DIRECTORY_SEPARATOR));
        $this->assertTrue(Path::isDirectorySeparator('/'));

        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertTrue(Path::isDirectorySeparator('\\'));
        } else {
            // macos or unix
            $this->assertFalse(Path::isDirectorySeparator('\\'));
        }

        $this->assertFalse(Path::isDirectorySeparator('')); // empty string
        $this->assertFalse(Path::isDirectorySeparator('|'));
        $this->assertFalse(Path::isDirectorySeparator('//'));
    }

    public function testEndsInDirectorySeparator(): void
    {
        $this->assertTrue(Path::endsInDirectorySeparator('/home/user/'));
        $this->assertTrue(Path::endsInDirectorySeparator('/home/user/foo.txt/'));

        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertTrue(Path::endsInDirectorySeparator('C:\Windows\System32\\'));
        } else {
            // macos or unix
            $this->assertFalse(Path::endsInDirectorySeparator('C:\Windows\System32\\'));
        }

        $this->assertFalse(Path::endsInDirectorySeparator('')); // empty string
        $this->assertFalse(Path::endsInDirectorySeparator('/foo/bar.txt'));
        $this->assertFalse(Path::endsInDirectorySeparator('/foo/bar'));
    }

    public function testHasRoot(): void
    {
        $this->assertTrue(Path::hasRoot('file://foo/bar/baz.txt'));
        $this->assertTrue(Path::hasRoot('/foo/bar'));
        $this->assertFalse(Path::hasRoot('foo/bar'));
        $this->assertFalse(Path::hasRoot('../foo/bar'));
        $this->assertFalse(Path::hasRoot('~/todo.txt'));

        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertTrue(Path::hasRoot('\foo\bar'));
            $this->assertTrue(Path::hasRoot('C:\Windows'));
            $this->assertTrue(Path::hasRoot('C:Windows'));
            $this->assertFalse(Path::hasRoot('desktop.ini'));
        }
    }

    public function testGetRoot(): void
    {
        $this->assertSame('file://', Path::getRoot('file://foo/bar/baz.txt'));
        $this->assertSame('/', Path::getRoot('/foo/bar'));
        $this->assertSame('', Path::getRoot('foo/bar'));
        $this->assertSame('', Path::getRoot('../foo/bar'));
        $this->assertSame('', Path::getRoot('~/todo.txt'));

        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertSame('C:/', Path::getRoot('C:/Windows'));
            $this->assertSame('C:/', Path::getRoot('C:Windows'));
            $this->assertSame('C:/', Path::getRoot('C:\Windows'));
            $this->assertSame('/', Path::getRoot('\foo\bar'));
        }
    }

    public function testIsAbsolute(): void
    {
        $this->assertTrue(Path::isAbsolute('/foo/bar/baz.bin'));
        $this->assertTrue(Path::isAbsolute('/mnt'));
        $this->assertTrue(Path::isAbsolute('/'));
        $this->assertTrue(Path::isAbsolute('file://text.txt'));
        $this->assertFalse(Path::isAbsolute('foo/bar'));
        $this->assertFalse(Path::isAbsolute('./foo/bar'));
        $this->assertFalse(Path::isAbsolute('../foo/bar'));
        $this->assertFalse(Path::isAbsolute('')); // empty string

        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertTrue(Path::isAbsolute('C:/Windows'));
            $this->assertTrue(Path::isAbsolute('C:\Windows'));
            $this->assertTrue(Path::isAbsolute('C:Windows'));
            $this->assertTrue(Path::isAbsolute('\foo\bar'));
            $this->assertTrue(Path::isAbsolute('\\'));
        }
    }

    public function testHasExtension(): void
    {
        $this->assertTrue(Path::hasExtension(__FILE__));
        $this->assertTrue(Path::hasExtension('.gitignore'));
        $this->assertTrue(Path::hasExtension('/home/user/.gitignore'));
        $this->assertTrue(Path::hasExtension('path/to/foo.tar.gz'));
        $this->assertFalse(Path::hasExtension('/foo/bar.txt/'));
        $this->assertFalse(Path::hasExtension('/foo/bar.')); // trailing period
        $this->assertFalse(Path::hasExtension('/foo/bar'));
        $this->assertFalse(Path::hasExtension('')); // empty string
    }

    public function testGetExtension(): void
    {
        $this->assertSame('.php', Path::getExtension(__FILE__));
        $this->assertSame('.gitignore', Path::getExtension('.gitignore'));
        $this->assertSame('.gitignore', Path::getExtension('/home/user/.gitignore'));
        $this->assertSame('.gz', Path::getExtension('path/to/foo.tar.gz'));
        $this->assertSame('', Path::getExtension('/foo/bar.txt/'));
        $this->assertSame('', Path::getExtension('/foo/bar.')); // trailing period
        $this->assertSame('', Path::getExtension('/foo/bar'));
        $this->assertSame('', Path::getExtension('')); // empty string
    }

    public function testChangeExtension(): void
    {
        $this->assertSame('/foo/bar/main.c', Path::changeExtension('/foo/bar/main.h', '.c'));
        $this->assertSame('main.c', Path::changeExtension('main.h', 'c')); // without leading period
        $this->assertSame('../foo.tar.gz', Path::changeExtension('../foo.tar.ball', '.gz'));
        $this->assertSame('/home/user/.gitconfig', Path::changeExtension('/home/user/.gitignore', '.gitconfig'));
        $this->assertSame('~/movie.mp4', Path::changeExtension('~/movie.', '.mp4'));
        $this->assertSame('/foo/bar/baz.', Path::changeExtension('/foo/bar/baz.bin', '.'));
        $this->assertSame('/foo/bar/baz.', Path::changeExtension('/foo/bar/baz.bin', ''));
    }

    public function testGetDirectoryNameReturnsDirName(): void
    {
        $this->assertSame(__DIR__, Path::getDirectory(__FILE__));
        $this->assertSame('/foo/bar/baz', Path::getDirectory('/foo/bar/baz/abc'));
        $this->assertSame('a/b/c', Path::getDirectory('a/b/c/d.png'));
        $this->assertSame('..', Path::getDirectory('../foo.php'));
        $this->assertSame('.', Path::getDirectory('./bar.php'));
        $this->assertSame('/', Path::getDirectory('/baz.php'));
    }

    public function testGetDirectoryNameReturnsEmptyString(): void
    {
        $this->assertSame('', Path::getDirectory('FileName-only'));
        $this->assertSame('', Path::getDirectory(''));
    }

    public function testGetDirectoryNameTrimsEndingDirectorySeparator(): void
    {
        $this->assertSame('/foo/bar', Path::getDirectory('/foo/bar/'));
        $this->assertSame('/foo/bar', Path::getDirectory('/foo/bar//'));
    }

    public function testChangeExtensionRemovesExtensionIfNullIsPassed(): void
    {
        $this->assertSame('~/todo', Path::changeExtension('~/todo.txt', null));
        $this->assertSame('~/', Path::changeExtension('~/.gitignore', null));
    }

    public function testGetFileNameReturnsFilename(): void
    {
        $this->assertSame('PathTest.php', Path::getFilename(__FILE__));
        $this->assertSame('.gitignore', Path::getFilename('/foo/bar/.gitignore'));
        $this->assertSame('File.txt', Path::getFilename('File.txt'));
        $this->assertSame('Shopping.txt', Path::getFilename('../Shopping.txt'));
    }

    public function testGetFileNameReturnsEmptyStringIfThePathDoesNotContainAFilename(): void
    {
        $this->assertSame('', Path::getFilename('/foo/bar/'));
        $this->assertSame('', Path::getFilename('/foo/bar/'));
        $this->assertSame('', Path::getFilename('/'));
        $this->assertSame('', Path::getFilename(''));
    }

    public function testGetFileNameReturnsPeriodIfPathIsAPeriodOrEndsWithAPeriod(): void
    {
        $this->assertSame('.', Path::getFilename('/foo/bar/.'));
        $this->assertSame('.', Path::getFilename('.'));
    }

    public function testGetFilenameWithoutExtension(): void
    {
        $this->assertSame('PathTest', Path::getFilenameWithoutExtension(__FILE__));
        $this->assertSame('File', Path::getFilenameWithoutExtension('File.txt'));
        $this->assertSame('', Path::getFilenameWithoutExtension('.gitignore'));
    }

    public function testCanonicalize(): void
    {
        $this->assertSame('/foo/baz', Path::canonicalize('/foo/bar/../baz'));
        $this->assertSame('/foo', Path::canonicalize('/foo/bar/../'));
        $this->assertSame('/foo/bar', Path::canonicalize('/foo/./bar'));
        $this->assertSame('/foo/bar', Path::canonicalize('/foo/bar/.'));
    }

    public function testCanonicalizeKeepsLeadingRelativeParentDirectoryDots(): void
    {
        $this->assertSame('../foo/bar', Path::canonicalize('../foo/bar'));
        $this->assertSame('../foo/baz', Path::canonicalize('../foo/./bar/../baz'));
        $this->assertSame('foo/bar', Path::canonicalize('./foo/bar'));
    }

    public function testCombine(): void
    {
        $this->assertSame('', Path::combine()); // no args
        $this->assertSame('', Path::combine(''));
        $this->assertSame('Hello', Path::combine('Hello')); // single argument
        $this->assertSame('foo/bar', Path::combine('foo', 'bar'));
        $this->assertSame('../foo/bar/./baz/file.txt', Path::combine('..', 'foo/bar', './baz', 'file.txt'));
    }

    public function testCombineDiscardsPreviousArgumentsIfArgumentIsARootedPath(): void
    {
        $this->assertSame('/home/user', Path::combine('/abc', 'def', '/home', 'user'));
        $this->assertSame('file://home/beta', Path::combine('a', 'b', 'c', 'file://home', 'beta'));
        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertSame('C:/Windows', Path::combine('Users', 'Admin', 'C:', 'Windows'));
            $this->assertSame('\foo/bar', Path::combine('Users', '\foo', 'bar'));
        }
    }

    public function testJoin(): void
    {
        $this->assertSame('', Path::join()); // no args
        $this->assertSame('', Path::join(''));
        $this->assertSame('Hello', Path::join('Hello')); // single argument
        $this->assertSame('foo/bar', Path::join('foo', 'bar'));
        $this->assertSame('../foo/bar/./baz/file.txt', Path::join('..', 'foo/bar', './baz', 'file.txt'));
    }

    public function testJoinConcatenatesAllArguments(): void
    {
        $this->assertSame('/abc/def/home/user', Path::join('/abc', 'def', '/home', 'user'));
        $this->assertSame('a/b/c/file://home/beta', Path::join('a', 'b', 'c', 'file://home', 'beta'));
        if (Environment::IS_WINDOWS_MACHINE) {
            $this->assertSame('Users/Admin/C:/Windows', Path::join('Users', 'Admin', 'C:', 'Windows'));
            $this->assertSame('Users\foo/bar', Path::join('Users', '\foo', 'bar'));
        }
    }
}
