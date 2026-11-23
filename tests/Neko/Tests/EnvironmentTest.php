<?php declare(strict_types=1);
namespace Neko\Tests;

use Neko\Environment;
use Neko\IO\Path;
use PHPUnit\Framework\TestCase;
use function getcwd;
use function getenv;
use function gethostname;
use function getmypid;
use function putenv;

final class EnvironmentTest extends TestCase
{
    private const array VARIABLES = [
        'NEKO_TEST_VAR',
        'NEKO_TEST_EMPTY',
        'NEKO_TEST_UNDEFINED',
        'USERPROFILE',
        'HOMEDRIVE',
        'HOMEPATH',
        'HOME',
        'USERNAME',
    ];

    private array $saved = [];

    protected function setUp(): void
    {
        foreach (self::VARIABLES as $name) {
            $this->saved[$name] = getenv($name);
        }

        putenv('NEKO_TEST_VAR=neko');
        putenv('NEKO_TEST_EMPTY=');
        putenv('NEKO_TEST_UNDEFINED');
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }
    }

    #region Constants tests
    public function testPlatformConstantsAreMutuallyExclusive(): void
    {
        $this->assertFalse(Environment::IS_WINDOWS && Environment::IS_UNIX);
    }
    #endregion

    #region Environment::get() tests
    public function testGetReturnsTheValue(): void
    {
        $this->assertSame('neko', Environment::get('NEKO_TEST_VAR'));
    }

    public function testGetReturnsNullIfUndefined(): void
    {
        $this->assertNull(Environment::get('NEKO_TEST_UNDEFINED'));
    }

    public function testGetReturnsNullIfEmpty(): void
    {
        $this->assertNull(Environment::get('NEKO_TEST_EMPTY'));
    }
    #endregion

    #region Environment::getAll() tests
    public function testGetAllContainsDefinedVariables(): void
    {
        $variables = Environment::getAll();
        $this->assertSame('neko', $variables['NEKO_TEST_VAR'] ?? null);
        $this->assertArrayNotHasKey('NEKO_TEST_UNDEFINED', $variables);
        $this->assertArrayNotHasKey('NEKO_TEST_EMPTY', $variables);
    }
    #endregion

    #region Environment::getCurrentDirectory() tests
    public function testGetCurrentDirectoryReturnsTheWorkingDirectory(): void
    {
        $this->assertSame(getcwd(), Environment::getCurrentDirectory());
    }
    #endregion

    #region Environment::getHomeDirectory() tests
    public function testGetHomeDirectoryReturnsTheNativeVariable(): void
    {
        if (Environment::IS_WINDOWS) {
            putenv('USERPROFILE=C:\\Users\\neko');
            putenv('HOME=C:\\Other');
            $this->assertSame('C:\\Users\\neko', Environment::getHomeDirectory());
        } else {
            putenv('HOME=/home/neko');
            $this->assertSame('/home/neko', Environment::getHomeDirectory());
        }
    }

    public function testGetHomeDirectoryFallsBackToHomeDriveAndHomePathOnWindows(): void
    {
        if (!Environment::IS_WINDOWS) {
            $this->markTestSkipped('Windows only');
        }

        putenv('USERPROFILE');
        putenv('HOMEDRIVE=D:');
        putenv('HOMEPATH=\\Users\\neko');
        $this->assertSame('D:\\Users\\neko', Environment::getHomeDirectory());
    }

    public function testGetHomeDirectoryReturnsNullIfItCannotBeDetermined(): void
    {
        if (!Environment::IS_WINDOWS) {
            $this->markTestSkipped('On Unix, the user database may still provide the home directory');
        }

        putenv('USERPROFILE');
        putenv('HOMEDRIVE');
        putenv('HOMEPATH');
        putenv('HOME');
        $this->assertNull(Environment::getHomeDirectory());
    }
    #endregion

    #region Environment::expand() tests
    public function testExpandExpandsBothForms(): void
    {
        $this->assertSame('neko/neko', Environment::expand('$NEKO_TEST_VAR/${NEKO_TEST_VAR}'));
    }

    public function testExpandAllowsBracedNamesFollowedByText(): void
    {
        $this->assertSame('nekos', Environment::expand('${NEKO_TEST_VAR}s'));
    }

    public function testExpandLeavesUndefinedAndEmptyReferencesUnchanged(): void
    {
        $str = '$NEKO_TEST_UNDEFINED ${NEKO_TEST_UNDEFINED} $NEKO_TEST_EMPTY';
        $this->assertSame($str, Environment::expand($str));
    }

    public function testExpandIgnoresInvalidReferences(): void
    {
        $str = '$1 $ ${} ${NEKO_TEST_VAR $5.00';
        $this->assertSame($str, Environment::expand($str));
    }
    #endregion
}
