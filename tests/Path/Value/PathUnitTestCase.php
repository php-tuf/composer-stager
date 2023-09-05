<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use AssertionError;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use ReflectionClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\Path
 *
 * @covers ::__construct
 * @covers ::absolute
 * @covers ::doAbsolute
 * @covers ::isAbsolute
 * @covers ::relative
 * @covers \PhpTuf\ComposerStager\Internal\Path\Value\Path::getcwd
 */
abstract class PathUnitTestCase extends TestCase
{
    /** @dataProvider providerBasicFunctionality */
    public function testBasicFunctionality(
        string $given,
        string $basePath,
        bool $isAbsolute,
        string $absolute,
        string $relativeBase,
        string $relative,
    ): void {
        $equalInstance = new Path($given);
        $unequalInstance = new Path(__DIR__);
        $relativeBase = new Path($relativeBase);
        $sut = new Path($given);

        // Dynamically override basePath.
        $overrideBasePath = static function (PathInterface $pathObject, string $basePathOverride): void {
            $reflection = new ReflectionClass($pathObject);
            $reflection->newInstanceWithoutConstructor();
            $basePathProperty = $reflection->getProperty('basePath');
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

    abstract public function providerBasicFunctionality(): array;

    /** @dataProvider providerBaseDirArgument */
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $basePath, string $absolute): void
    {
        $sut = new Path($path, $basePath);

        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
    }

    abstract public function providerBaseDirArgument(): array;

    /**
     * @covers ::absolute
     * @covers ::doAbsolute
     * @covers ::relative
     */
    public function testNonAbsoluteBasePath(): void
    {
        $path = '../arbitrary/../path.txt';
        $canonicalizedPath = PathHelper::fixSeparators('../path.txt');
        $sut = new Path($path);

        $invalidBasePath = '../relative-path.txt';
        $reflection = new ReflectionClass($sut);
        $reflection->newInstanceWithoutConstructor();
        $basePath = $reflection->getProperty('basePath');
        $basePath->setValue($sut, $invalidBasePath);

        // Disable assertions so production error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 0);

        self::assertSame($canonicalizedPath, $sut->absolute(), '::absolute() returned canonicalized path on failure.');
        self::assertSame($canonicalizedPath, $sut->relative($sut), '::relative() returned canonicalized path on failure.');

        // Re-enable assertions so development error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 1);

        $assertException = static function (string $methodName, callable $callback) use ($invalidBasePath): void {
            try {
                $callback();
                self::fail(sprintf('::%s() failed to throw "AssertionError".', $methodName));
            } catch (AssertionError $e) {
                self::assertSame(
                    sprintf('Base paths must be absolute. Got %s.', $invalidBasePath),
                    $e->getMessage(),
                    sprintf('::%s() used the correct exception message.', $methodName),
                );
            }
        };

        // Each method that ultimately tries to get the absolute path should throw an AssertionError if it fails.
        $assertException('absolute', static function () use ($sut): void {
            $sut->absolute();
        });
        $assertException('relative', static function () use ($sut): void {
            $sut->relative($sut);
        });
    }
}
