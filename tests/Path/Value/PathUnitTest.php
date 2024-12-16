<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\BuiltinFunctionMocker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionClass;

#[CoversClass(Path::class)]
final class PathUnitTest extends TestCase
{
    private function createSut(mixed ...$arguments): Path
    {
        $pathHelper = self::createPathHelper();

        return new Path($pathHelper, ...func_get_args());
    }

    /** @noinspection PhpConditionAlreadyCheckedInspection */
    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(
        string $given,
        string $basePath,
        bool $expectedIsAbsolute,
        string $expectedAbsolute,
        string $relativeBase,
        string $expectedRelative,
    ): void {
        $equalInstance = self::createPath($given);
        $unequalInstance = self::createPath(__DIR__);
        $relativeBase = self::createPath($relativeBase);
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

        self::assertEquals($expectedIsAbsolute, $sut->isAbsolute(), 'Correctly determined whether given path was absolute.');
        self::assertEquals(!$expectedIsAbsolute, $sut->isRelative(), 'Correctly determined whether given path was relative.');
        self::assertEquals($expectedAbsolute, $sut->absolute(), 'Got absolute path.');
        self::assertEquals($expectedRelative, $sut->relative($relativeBase), 'Got absolute path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($expectedAbsolute, $sut->absolute(), 'Retained correct value after changing working directory.');
        self::assertEquals($sut, $equalInstance, 'Path value still considered equal to another instance with the same input after changing working directory.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');
    }

    public static function providerBasicFunctionality(): array
    {
        return array_merge(
            self::providerBasicFunctionalityUnixLike(),
            self::providerBasicFunctionalityWindows(),
        );
    }

    public static function providerBasicFunctionalityUnixLike(): array
    {
        $data = [
            // Special base paths.
            'Unix-like: Path as empty string ()' => [
                'given' => '',
                'basePath' => '/var/one',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/one',
                'relativeBase' => '/tmp/two',
                'expectedRelative' => '/tmp/two',
            ],
            'Unix-like: Path as dot (.)' => [
                'given' => '.',
                'basePath' => '/var/three',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/three',
                'relativeBase' => '/tmp/four',
                'expectedRelative' => '/tmp/four',
            ],
            'Unix-like: Path as dot-slash (./)' => [
                'given' => './',
                'basePath' => '/var/five',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/five',
                'relativeBase' => '/tmp/six',
                'expectedRelative' => '/tmp/six',
            ],
            // Relative paths.
            'Unix-like: Relative path as simple string' => [
                'given' => 'one',
                'basePath' => '/var',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/one',
                'relativeBase' => '/tmp',
                'expectedRelative' => '/tmp/one',
            ],
            'Unix-like: Relative path as space ( )' => [
                'given' => ' ',
                'basePath' => '/var/two',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'expectedRelative' => '/tmp/three/ ',
            ],
            'Unix-like: Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'basePath' => '/var',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'expectedRelative' => '/tmp/one/two/three/four/five',
            ],
            'Unix-like: Relative path with trailing slash' => [
                'given' => 'one/two/',
                'basePath' => '/var',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/one/two',
                'relativeBase' => '/tmp',
                'expectedRelative' => '/tmp/one/two',
            ],
            'Unix-like: Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'basePath' => '/var/four',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'expectedRelative' => '/tmp/five/one/two/three',
            ],
            'Unix-like: Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'basePath' => '/var/seven/eight',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'expectedRelative' => '/tmp/nine/two/five',
            ],
            'Unix-like: Relative path with leading double dots (..) and root base path' => [
                'given' => '../one/two',
                'basePath' => '/',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/one/two',
                'relativeBase' => '/three/..',
                'expectedRelative' => '/one/two',
            ],
            'Unix-like: Silly combination of relative path as double dots (..) with root base path' => [
                'given' => '..',
                'basePath' => '/',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/',
                'relativeBase' => '/',
                'expectedRelative' => '/',
            ],
            'Unix-like: Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'basePath' => '/seven/eight/nine/ten',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'expectedRelative' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Unix-like: Absolute path to the root' => [
                'given' => '/',
                'basePath' => '/',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/',
                'relativeBase' => '/',
                'expectedRelative' => '/',
            ],
            'Unix-like: Absolute path as simple string' => [
                'given' => '/one',
                'basePath' => '/var',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/one',
                'relativeBase' => '/tmp',
                'expectedRelative' => '/one',
            ],
            'Unix-like: Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'basePath' => '/var/six/seven/eight/nine',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'expectedRelative' => '/one/two/three/four/five',
            ],
            'Unix-like: Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'basePath' => '/var/seven/eight/nine',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'expectedRelative' => '/one/six',
            ],
            // Protocols.
            'Path with protocol: ftp://' => [
                'given' => 'ftp://example.com/one/two/three.txt',
                'basePath' => '/var',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Path with protocol: file:///' => [
                'given' => 'file:///one/two/three.txt',
                'basePath' => '/var',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'file:///one/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => 'file:///one/two/three.txt',
            ],
            'Relative with base path with protocol' => [
                'given' => 'one/two/three.txt',
                'basePath' => 'ftp://example.com',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Relative with base path with protocol with trailing slash' => [
                'given' => 'one/two/three.txt',
                'basePath' => 'ftp://example.com/',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'ftp://example.com/one/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => 'ftp://example.com/one/two/three.txt',
            ],
            'Absolute with base path with protocol' => [
                'given' => '/one/two/three.txt',
                'basePath' => 'ftp://example.com',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'expectedRelative' => '/one/two/three.txt',
            ],
            'Absolute with base path with protocol with trailing slash' => [
                'given' => '/one/two/three.txt',
                'basePath' => 'ftp://example.com/',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => '/one/two/three.txt',
                'relativeBase' => 'ftp://example.com/one/two/three.txt',
                'expectedRelative' => '/one/two/three.txt',
            ],
            'Non-canonicalized path with protocol' => [
                'given' => 'vfs://example.com/one/../two/three.txt',
                'basePath' => '/var',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'vfs://example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => 'vfs://example.com/two/three.txt',
            ],
            // Generally speaking, it would probably be better if an invalid
            // protocol caused a failure. But since protocols are officially
            // unsupported and used only internally for testing, it's sufficient
            // just to document that this is the current behavior.
            'Invalid protocol' => [
                'given' => '1ftp://example.com/one/../two/three.txt',
                'basePath' => '/var',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => '/var/1ftp:/example.com/two/three.txt',
                'relativeBase' => '/tmp',
                'expectedRelative' => '/tmp/1ftp:/example.com/two/three.txt',
            ],
        ];

        foreach ($data as $label => $datum) {
            unset($data[$label]);
            $data['Unix-like: ' . $label] = $datum;
        }

        return $data;
    }

    public static function providerBasicFunctionalityWindows(): array
    {
        $data = [
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'One',
                'basePath' => 'C:\\Windows',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/One',
                'relativeBase' => 'D:\\Users',
                'expectedRelative' => 'D:/Users/One',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'basePath' => 'C:\\Windows\\Two',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/Two/ ',
                'relativeBase' => 'D:\\Users\\Three',
                'expectedRelative' => 'D:/Users/Three/ ',
            ],
            'Relative path with nesting' => [
                'given' => 'One\\Two\\Three\\Four\\Five',
                'basePath' => 'C:\\Windows',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/One/Two/Three/Four/Five',
                'relativeBase' => 'D:\\Users',
                'expectedRelative' => 'D:/Users/One/Two/Three/Four/Five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'One\\Two\\',
                'basePath' => 'C:\\Windows',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/One/Two',
                'relativeBase' => 'D:\\Users',
                'expectedRelative' => 'D:/Users/One/Two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'One\\\\Two\\\\\\\\Three',
                'basePath' => 'C:\\Windows\\Four',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/Four/One/Two/Three',
                'relativeBase' => 'D:\\Users\\Five',
                'expectedRelative' => 'D:/Users/Five/One/Two/Three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '..\\One\\..\\Two\\Three\\Four\\..\\..\\Five\\Six\\..',
                'basePath' => 'C:\\Windows\\Seven\\Eight',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Windows/Seven/Two/Five',
                'relativeBase' => 'D:\\Users\\Nine\\Ten',
                'expectedRelative' => 'D:/Users/Nine/Two/Five',
            ],
            'Relative path with leading double dots (..) and root path base path' => [
                'given' => '..\\One\\Two',
                'basePath' => 'C:\\',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/One/Two',
                'relativeBase' => 'D:\\',
                'expectedRelative' => 'D:/One/Two',
            ],
            'Silly combination of relative path as double dots (..) with root path base path' => [
                'given' => '..',
                'basePath' => 'C:\\',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/',
                'relativeBase' => 'D:\\',
                'expectedRelative' => 'D:/',
            ],
            'Crazy relative path' => [
                'given' => 'One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'basePath' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'expectedIsAbsolute' => false,
                'expectedAbsolute' => 'C:/Seven/Eight/Nine/Ten/One/Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Thirteen\\Fourteen',
                'expectedRelative' => 'D:/Eleven/Twelve/Thirteen/Fourteen/One/Six',
            ],
            // Absolute paths from the root of a specific drive.
            'Absolute path to the root of a specific drive' => [
                'given' => 'D:\\',
                'basePath' => 'C:\\',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'D:/',
                'relativeBase' => 'D:\\',
                'expectedRelative' => 'D:/',
            ],
            'Absolute path from the root of a specific drive as simple string' => [
                'given' => 'D:\\One',
                'basePath' => 'C:\\Windows',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'D:/One',
                'relativeBase' => 'D:\\Users',
                'expectedRelative' => 'D:/One',
            ],
            'Absolute path from the root of a specific drive with nesting' => [
                'given' => 'D:\\One\\Two\\Three\\Four\\Five',
                'basePath' => 'C:\\Windows\\Six\\Seven\\Eight\\Nine',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'D:/One/Two/Three/Four/Five',
                'relativeBase' => 'D:\\Users',
                'expectedRelative' => 'D:/One/Two/Three/Four/Five',
            ],
            'Crazy absolute path from the root of a specific drive' => [
                'given' => 'D:\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'basePath' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'expectedIsAbsolute' => true,
                'expectedAbsolute' => 'D:/One/Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Fourteen',
                'expectedRelative' => 'D:/One/Six',
            ],
        ];

        foreach ($data as $label => $datum) {
            unset($data[$label]);
            $data['Windows: ' . $label] = $datum;
        }

        return $data;
    }

    #[DataProvider('providerBaseDirArgument')]
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $basePath, string $expectedAbsolute): void
    {
        $sut = $this->createSut($path, $basePath);

        self::assertEquals($expectedAbsolute, $sut->absolute(), 'Got absolute path.');
    }

    public static function providerBaseDirArgument(): array
    {
        $cwd = str_replace('\\', '/', getcwd());

        return [
            'Unix-like: with $basePath argument.' => [
                'path' => 'one',
                'basePath' => self::createPath('/arg'),
                'expectedAbsolute' => '/arg/one',
            ],
            'Unix-like: with null $basePath argument' => [
                'path' => 'one',
                'basePath' => null,
                'expectedAbsolute' => sprintf('%s/one', $cwd),
            ],
            'Windows: with $basePath argument.' => [
                'path' => 'One',
                'basePath' => self::createPath('C:\\Arg'),
                'expectedAbsolute' => 'C:/Arg/One',
            ],
            'Windows: With null $basePath argument' => [
                'path' => 'One',
                'basePath' => null,
                'expectedAbsolute' => sprintf('%s/One', $cwd),
            ],
        ];
    }

    #[DataProvider('providerGetCwd')]
    #[RunInSeparateProcess]
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

    public static function providerGetCwd(): array
    {
        return [
            'Normal return' => [
                'builtInReturn' => __DIR__,
                'md5' => '',
                'sysGetTempDir' => '',
                'expectedSutReturn' => __DIR__,
            ],
            'Failure' => [
                'builtInReturn' => false,
                'md5' => '1234',
                'sysGetTempDir' => '/temp',
                'expectedSutReturn' => '/temp/composer-stager/error-1234',
            ],
        ];
    }
}
