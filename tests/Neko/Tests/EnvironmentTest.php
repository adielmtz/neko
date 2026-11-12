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
        $this->assertFalse(Environment::IS_WINDOWS_MACHINE && Environment::IS_UNIX_MACHINE);
    }
    #endregion

    #region Environment::getEnvironmentVariable() tests
    public function testGetEnvironmentVariableReturnsTheValue(): void
    {
        $this->assertSame('neko', Environment::getEnvironmentVariable('NEKO_TEST_VAR'));
    }

    public function testGetEnvironmentVariableReturnsNullIfUndefined(): void
    {
        $this->assertNull(Environment::getEnvironmentVariable('NEKO_TEST_UNDEFINED'));
    }

    public function testGetEnvironmentVariableReturnsNullIfEmpty(): void
    {
        $this->assertNull(Environment::getEnvironmentVariable('NEKO_TEST_EMPTY'));
    }
    #endregion

    #region Environment::getEnvironmentVariables() tests
    public function testGetEnvironmentVariablesContainsDefinedVariables(): void
    {
        $variables = Environment::getEnvironmentVariables();
        $this->assertSame('neko', $variables['NEKO_TEST_VAR'] ?? null);
        $this->assertArrayNotHasKey('NEKO_TEST_UNDEFINED', $variables);
        $this->assertArrayNotHasKey('NEKO_TEST_EMPTY', $variables);
    }
    #endregion

    #region Environment::getTempDirectory() tests
    public function testGetTempDirectoryReturnsAnExistingDirectory(): void
    {
        $path = Environment::getTempDirectory();
        $this->assertDirectoryExists($path);
        $this->assertFalse(Path::endsInDirectorySeparator($path));
    }
    #endregion

    #region Environment::getCurrentDirectory() tests
    public function testGetCurrentDirectoryReturnsTheWorkingDirectory(): void
    {
        $this->assertSame(getcwd(), Environment::getCurrentDirectory());
    }
    #endregion

    #region Environment::getMachineName() tests
    public function testGetMachineNameReturnsTheHostName(): void
    {
        $this->assertSame(gethostname(), Environment::getMachineName());
    }
    #endregion

    #region Environment::getUserName() tests
    public function testGetUserNameReturnsUserNameOnWindows(): void
    {
        if (!Environment::IS_WINDOWS_MACHINE) {
            $this->markTestSkipped('Windows only');
        }

        putenv('USERNAME=neko');
        $this->assertSame('neko', Environment::getUserName());
    }

    public function testGetUserNameReturnsANonEmptyName(): void
    {
        $name = Environment::getUserName();
        $this->assertNotNull($name);
        $this->assertNotSame('', $name);
    }
    #endregion

    #region Environment::getProcessId() tests
    public function testGetProcessIdReturnsTheCurrentProcessId(): void
    {
        $this->assertSame(getmypid(), Environment::getProcessId());
    }
    #endregion

    #region Environment::getHomeDirectory() tests
    public function testGetHomeDirectoryReturnsTheNativeVariable(): void
    {
        if (Environment::IS_WINDOWS_MACHINE) {
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
        if (!Environment::IS_WINDOWS_MACHINE) {
            $this->markTestSkipped('Windows only');
        }

        putenv('USERPROFILE');
        putenv('HOMEDRIVE=D:');
        putenv('HOMEPATH=\\Users\\neko');
        $this->assertSame('D:\\Users\\neko', Environment::getHomeDirectory());
    }

    public function testGetHomeDirectoryReturnsNullIfItCannotBeDetermined(): void
    {
        if (!Environment::IS_WINDOWS_MACHINE) {
            $this->markTestSkipped('On Unix, the user database may still provide the home directory');
        }

        putenv('USERPROFILE');
        putenv('HOMEDRIVE');
        putenv('HOMEPATH');
        putenv('HOME');
        $this->assertNull(Environment::getHomeDirectory());
    }
    #endregion

    #region Environment::expandEnvironmentVariables() tests
    public function testExpandEnvironmentVariablesExpandsBothForms(): void
    {
        $this->assertSame('neko/neko', Environment::expandEnvironmentVariables('$NEKO_TEST_VAR/${NEKO_TEST_VAR}'));
    }

    public function testExpandEnvironmentVariablesAllowsBracedNamesFollowedByText(): void
    {
        $this->assertSame('nekos', Environment::expandEnvironmentVariables('${NEKO_TEST_VAR}s'));
    }

    public function testExpandEnvironmentVariablesLeavesUndefinedAndEmptyReferencesUnchanged(): void
    {
        $str = '$NEKO_TEST_UNDEFINED ${NEKO_TEST_UNDEFINED} $NEKO_TEST_EMPTY';
        $this->assertSame($str, Environment::expandEnvironmentVariables($str));
    }

    public function testExpandEnvironmentVariablesIgnoresInvalidReferences(): void
    {
        $str = '$1 $ ${} ${NEKO_TEST_VAR $5.00';
        $this->assertSame($str, Environment::expandEnvironmentVariables($str));
    }
    #endregion
}
