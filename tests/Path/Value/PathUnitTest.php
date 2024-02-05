<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use ReflectionClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\Path
 *
 * @covers ::__construct
 * @covers ::absolute
 * @covers ::doAbsolute
 * @covers ::getcwd
 * @covers ::getProtocol
 * @covers ::hasProtocol
 * @covers ::isAbsolute
 * @covers ::relative
 * @covers ::stripProtocol
 */
final class PathUnitTest extends TestCase
{
    private function createSut(mixed ...$arguments): Path
    {
        $pathHelper = PathTestHelper::createPathHelper();

        return new Path($pathHelper, ...func_get_args());
    }

    /**
     * @dataProvider providerBasicFunctionality
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testBasicFunctionality(
        string $given,
        string $basePath,
        bool $isAbsolute,
        string $absolute,
        string $relativeBase,
        string $relative,
    ): void {
        $equalInstance = PathTestHelper::createPath($given);
        $unequalInstance = PathTestHelper::createPath(__DIR__);
        $relativeBase = PathTestHelper::createPath($relativeBase);
        $sut = $this->createSut($given);

        // Dynamically override basePath.
        $overrideBasePath = static function (PathInterface $pathObject, string $basePathOverride): void {
            $reflection = new ReflectionClass($pathObject);
            $reflection->newInstanceWithoutConstructor();
            $basePathProperty = $reflection->getProperty('basePathAbsolute');
            $basePathProperty->setValue($pathObject, $basePathOverride);
        };
        $overrideBasePath($sut, $basePath);
        $overrideBasePath($equalInstance, $basePath);

        self::assertEquals($isAbsolute, $sut->isAbsolute(), 'Correctly determined whether given path was relative.');
        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
        self::assertEquals($relative, $sut->relative($relativeBase), 'Got absolute path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($absolute, $sut->absolute(), 'Retained correct value after changing working directory.');
        self::assertEquals($sut, $equalInstance, 'Path value still considered equal to another instance with the same input after changing working directory.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');
    }

    public function providerBasicFunctionality(): array
    {
        $data = [];

        foreach ($this->providerBasicFunctionalityUnixLike() as $description => $datum) {
            $data[sprintf('Unix-like: %s', $description)] = $datum;
        }

        foreach ($this->providerBasicFunctionalityWindows() as $description => $datum) {
            $data[sprintf('Windows: %s', $description)] = $datum;
        }

        return $data;
    }

    public function providerBasicFunctionalityUnixLike(): array
    {
        return [
            // Special base paths.
            'Unix-like: Path as empty string ()' => [
                'given' => '',
                'baseDir' => '/var/one',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp/two',
                'relative' => '/tmp/two',
            ],
            'Unix-like: Path as dot (.)' => [
                'given' => '.',
                'baseDir' => '/var/three',
                'isAbsolute' => false,
                'absolute' => '/var/three',
                'relativeBase' => '/tmp/four',
                'relative' => '/tmp/four',
            ],
            'Unix-like: Path as dot-slash (./)' => [
                'given' => './',
                'baseDir' => '/var/five',
                'isAbsolute' => false,
                'absolute' => '/var/five',
                'relativeBase' => '/tmp/six',
                'relative' => '/tmp/six',
            ],
            // Relative paths.
            'Unix-like: Relative path as simple string' => [
                'given' => 'one',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one',
            ],
            'Unix-like: Relative path as space ( )' => [
                'given' => ' ',
                'baseDir' => '/var/two',
                'isAbsolute' => false,
                'absolute' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'relative' => '/tmp/three/ ',
            ],
            'Unix-like: Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two/three/four/five',
            ],
            'Unix-like: Relative path with trailing slash' => [
                'given' => 'one/two/',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two',
            ],
            'Unix-like: Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'baseDir' => '/var/four',
                'isAbsolute' => false,
                'absolute' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'relative' => '/tmp/five/one/two/three',
            ],
            'Unix-like: Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'baseDir' => '/var/seven/eight',
                'isAbsolute' => false,
                'absolute' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'relative' => '/tmp/nine/two/five',
            ],
            'Unix-like: Relative path with leading double dots (..) and root base path' => [
                'given' => '../one/two',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/one/two',
                'relativeBase' => '/three/..',
                'relative' => '/one/two',
            ],
            'Unix-like: Silly combination of relative path as double dots (..) with root base path' => [
                'given' => '..',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Unix-like: Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/seven/eight/nine/ten',
                'isAbsolute' => false,
                'absolute' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'relative' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Unix-like: Absolute path to the root' => [
                'given' => '/',
                'baseDir' => '/',
                'isAbsolute' => true,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Unix-like: Absolute path as simple string' => [
                'given' => '/one',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => '/one',
                'relativeBase' => '/tmp',
                'relative' => '/one',
            ],
            'Unix-like: Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'baseDir' => '/var/six/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'relative' => '/one/two/three/four/five',
            ],
            'Unix-like: Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/var/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'relative' => '/one/six',
            ],
            // Protocols.
            'Path with protocol: ftp://' => [
                'given' => 'ftp://example.com/one/two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Path with protocol: file:///' => [
                'given' => 'file:///one/two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'file:///one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'file:///one/two/three.txt',
            ],
            'Relative with base path with protocol' => [
                'given' => 'one/two/three.txt',
                'baseDir' => 'ftp://example.com',
                'isAbsolute' => false,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Relative with base path with protocol with trailing slash' => [
                'given' => 'one/two/three.txt',
                'baseDir' => 'ftp://example.com/',
                'isAbsolute' => false,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Absolute with base path with protocol' => [
                'given' => '/one/two/three.txt',
                'baseDir' => 'ftp://example.com',
                'isAbsolute' => true,
                'absolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'relative' => '/one/two/three.txt',
            ],
            'Absolute with base path with protocol with trailing slash' => [
                'given' => '/one/two/three.txt',
                'baseDir' => 'ftp://example.com/',
                'isAbsolute' => true,
                'absolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'relative' => '/one/two/three.txt',
            ],
            'Non-canonicalized path with protocol' => [
                'given' => 'vfs://example.com/one/../two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'vfs://example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'vfs://example.com/two/three.txt',
            ],
            // Generally speaking, it would be better if an invalid protocol
            // caused a failure. But since protocols are officially unsupported
            // and used only internally for testing, it's sufficient just to
            // document that this is the current behavior.
            'Invalid protocol' => [
                'given' => '1ftp://example.com/one/../two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/1ftp:/example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/1ftp:/example.com/two/three.txt',
            ],

        ];
    }

    public function providerBasicFunctionalityWindows(): array
    {
        return [
            // Special base paths.
            'Path as empty string ()' => [
                'given' => '',
                'baseDir' => '/var/one',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp/two',
                'relative' => '/tmp/two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'baseDir' => '/var/three',
                'isAbsolute' => false,
                'absolute' => '/var/three',
                'relativeBase' => '/tmp/four',
                'relative' => '/tmp/four',
            ],
            'Path as dot-slash (./)' => [
                'given' => './',
                'baseDir' => '/var/five',
                'isAbsolute' => false,
                'absolute' => '/var/five',
                'relativeBase' => '/tmp/six',
                'relative' => '/tmp/six',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'baseDir' => '/var/two',
                'isAbsolute' => false,
                'absolute' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'relative' => '/tmp/three/ ',
            ],
            'Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/one/two',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'baseDir' => '/var/four',
                'isAbsolute' => false,
                'absolute' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'relative' => '/tmp/five/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'baseDir' => '/var/seven/eight',
                'isAbsolute' => false,
                'absolute' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'relative' => '/tmp/nine/two/five',
            ],
            'Relative path with leading double dots (..) and root base path' => [
                'given' => '../one/two',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/one/two',
                'relativeBase' => '/three/..',
                'relative' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root base path' => [
                'given' => '..',
                'baseDir' => '/',
                'isAbsolute' => false,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/seven/eight/nine/ten',
                'isAbsolute' => false,
                'absolute' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'relative' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'baseDir' => '/',
                'isAbsolute' => true,
                'absolute' => '/',
                'relativeBase' => '/',
                'relative' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => '/one',
                'relativeBase' => '/tmp',
                'relative' => '/one',
            ],
            'Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'baseDir' => '/var/six/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'relative' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/var/seven/eight/nine',
                'isAbsolute' => true,
                'absolute' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'relative' => '/one/six',
            ],
            // Protocols.
            'Path with protocol: ftp://' => [
                'given' => 'ftp://example.com/one/two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Path with protocol: file:///' => [
                'given' => 'file:///one/two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'file:///one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'file:///one/two/three.txt',
            ],
            'Relative with base path with protocol' => [
                'given' => 'one/two/three.txt',
                'baseDir' => 'ftp://example.com',
                'isAbsolute' => false,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Relative with base path with protocol with trailing slash' => [
                'given' => 'one/two/three.txt',
                'baseDir' => 'ftp://example.com/',
                'isAbsolute' => false,
                'absolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Absolute with base path with protocol' => [
                'given' => '/one/two/three.txt',
                'baseDir' => 'ftp://example.com',
                'isAbsolute' => true,
                'absolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'relative' => '/one/two/three.txt',
            ],
            'Absolute with base path with protocol with trailing slash' => [
                'given' => '/one/two/three.txt',
                'baseDir' => 'ftp://example.com/',
                'isAbsolute' => true,
                'absolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'relative' => '/one/two/three.txt',
            ],
            'Non-canonicalized path with protocol' => [
                'given' => 'vfs://example.com/one/../two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'absolute' => 'vfs://example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => 'vfs://example.com/two/three.txt',
            ],
            // Generally speaking, it would be better if an invalid protocol
            // caused a failure. But since protocols are officially unsupported
            // and used only internally for testing, it's sufficient just to
            // document that this is the current behavior.
            'Invalid protocol' => [
                'given' => '1ftp://example.com/one/../two/three.txt',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'absolute' => '/var/1ftp:/example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'relative' => '/tmp/1ftp:/example.com/two/three.txt',
            ],
        ];
    }

    /** @dataProvider providerBaseDirArgument */
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $basePath, string $absolute): void
    {
        $sut = $this->createSut($path, $basePath);

        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
    }

    public function providerBaseDirArgument(): array
    {
        $cwd = str_replace('\\', '', getcwd());

        return [
            'Unix-like: with $basePath argument.' => [
                'path' => 'one',
                'baseDir' => self::createPath('/arg'),
                'absolute' => '/arg/one',
            ],
            'Windows: with $basePath argument.' => [
                'path' => 'One',
                'baseDir' => self::createPath('C:\\Arg'),
                'absolute' => 'C:/Arg/One',
            ],
            'With explicit null $basePath argument' => [
                'path' => 'one',
                'baseDir' => null,
                'absolute' => sprintf('%s/one', $cwd),
            ],
        ];
    }

    /**
     * @dataProvider providerGetCwd
     *
     * @runInSeparateProcess
     */
    public function testGetCwd(
        string|false $builtInReturn,
        string $md5,
        string $sysGetTempDir,
        string $expectedSutReturn,
    ): void {
        BuiltinFunctionMocker::mock([
            'getcwd' => $this->prophesize(TestSpyInterface::class),
            'md5' => $this->prophesize(TestSpyInterface::class),
            'sys_get_temp_dir' => $this->prophesize(TestSpyInterface::class),
        ]);
        BuiltinFunctionMocker::$spies['getcwd']
            ->report()
            ->shouldBeCalledOnce()
            ->willReturn($builtInReturn);
        BuiltinFunctionMocker::$spies['md5']
            ->report()
            ->willReturn($md5);
        BuiltinFunctionMocker::$spies['sys_get_temp_dir']
            ->report()
            ->willReturn($sysGetTempDir);

        $reflection = new ReflectionClass(Path::class);
        $sut = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getcwd');
        $actualSutReturn = $method->invoke($sut);

        self::assertSame($expectedSutReturn, $actualSutReturn);
    }

    public function providerGetCwd(): array
    {
        return [
            'Normal return' => [
                'builtInReturn' => __DIR__,
                'md5' => '',
                'sys_get_temp_dir' => '',
                'expectedSutReturn' => __DIR__,
            ],
            'Failure' => [
                'builtInReturn' => false,
                'md5' => '1234',
                'sys_get_temp_dir' => '/temp',
                'expectedSutReturn' => '/temp/composer-stager/error-1234',
            ],
        ];
    }
}
