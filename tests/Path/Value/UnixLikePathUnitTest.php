<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 *
 * @covers ::__construct
 * @covers ::doResolve
 * @covers ::isAbsolute
 * @covers ::makeAbsolute
 * @covers ::normalize
 * @covers ::raw
 * @covers ::resolved
 * @covers ::resolvedRelativeTo
 * @covers \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath::getcwd
 *
 * @group no_windows
 */
final class UnixLikePathUnitTest extends TestCase
{
    public string $baseDir;

    /** @dataProvider providerBasicFunctionality */
    public function testBasicFunctionality(
        string $given,
        string $baseDir,
        bool $isAbsolute,
        string $resolved,
        string $relativeBase,
        string $resolvedRelativeTo,
    ): void {
        $equalInstance = new UnixLikePath($given);
        $unequalInstance = new UnixLikePath(__DIR__);
        $relativeBase = new UnixLikePath($relativeBase);
        $sut = new UnixLikePath($given);

        // Dynamically override baseDir.
        $setBaseDir = function ($baseDir): void {
            $this->baseDir = $baseDir;
        };
        $setBaseDir->call($sut, $baseDir);
        $setBaseDir->call($equalInstance, $baseDir);

        self::assertEquals($resolved, $sut->resolved(), 'Got correct value via explicit method call.');

        self::assertEquals($isAbsolute, $sut->isAbsolute(), 'Correctly determined whether given path was relative.');
        self::assertEquals($given, $sut->raw(), 'Correctly returned raw path.');
        self::assertEquals($resolved, $sut->resolved(), 'Correctly resolved path.');
        self::assertEquals($resolvedRelativeTo, $sut->resolvedRelativeTo($relativeBase), 'Correctly resolved path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($resolved, $sut->resolved(), 'Retained correct value after changing working directory.');
        self::assertEquals($sut, $equalInstance, 'Path value still considered equal to another instance with the same input after changing working directory.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            // Special base directory paths.
            'Path as empty string ()' => [
                'given' => '',
                'baseDir' => '/var/one',
                'isAbsolute' => false,
                'resolved' => '/var/one',
                'relativeBase' => '/tmp/two',
                'resolvedRelativeTo' => '/tmp/two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'baseDir' => '/var/three',
                'isAbsolute' => false,
                'resolved' => '/var/three',
                'relativeBase' => '/tmp/four',
                'resolvedRelativeTo' => '/tmp/four',
            ],
            'Path as dot-slash (./)' => [
                'given' => './',
                'baseDir' => '/var/five',
                'isAbsolute' => false,
                'resolved' => '/var/five',
                'relativeBase' => '/tmp/six',
                'resolvedRelativeTo' => '/tmp/six',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'baseDir' => '/var/two',
                'isAbsolute' => false,
                'resolved' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'resolvedRelativeTo' => '/tmp/three/ ',
            ],
            'Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'baseDir' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one/two',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'baseDir' => '/var/four',
                'isAbsolute' => false,
                'resolved' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'resolvedRelativeTo' => '/tmp/five/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'baseDir' => '/var/seven/eight',
                'isAbsolute' => false,
                'resolved' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'resolvedRelativeTo' => '/tmp/nine/two/five',
            ],
            'Relative path with leading double dots (..) and root path base directory' => [
                'given' => '../one/two',
                'baseDir' => '/',
                'isAbsolute' => false,
                'resolved' => '/one/two',
                'relativeBase' => '/three/..',
                'resolvedRelativeTo' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root path base directory' => [
                'given' => '..',
                'baseDir' => '/',
                'isAbsolute' => false,
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/seven/eight/nine/ten',
                'isAbsolute' => false,
                'resolved' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'resolvedRelativeTo' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'baseDir' => '/',
                'isAbsolute' => true,
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'baseDir' => '/var',
                'isAbsolute' => true,
                'resolved' => '/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/one',
            ],
            'Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'baseDir' => '/var/six/seven/eight/nine',
                'isAbsolute' => true,
                'resolved' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'resolvedRelativeTo' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'baseDir' => '/var/seven/eight/nine',
                'isAbsolute' => true,
                'resolved' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'resolvedRelativeTo' => '/one/six',
            ],
        ];
    }

    /** @dataProvider providerBaseDirArgument */
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $baseDir, string $resolved): void
    {
        $sut = new UnixLikePath($path, $baseDir);

        self::assertEquals($resolved, $sut->resolved(), 'Correctly resolved path.');
    }

    public function providerBaseDirArgument(): array
    {
        return [
            'With $baseDir argument.' => [
                'path' => 'one',
                'baseDir' => new TestPath('/arg'),
                'resolved' => '/arg/one',
            ],
            'With explicit null $baseDir argument' => [
                'path' => 'one',
                'baseDir' => null,
                'resolved' => sprintf('%s/one', getcwd()),
            ],
        ];
    }
}
