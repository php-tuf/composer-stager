<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 *
 * @covers ::__construct
 * @covers ::doResolve
 * @covers ::isAbsolute
 * @covers ::makeAbsolute
 * @covers ::normalize
 * @covers ::raw
 * @covers ::resolve
 * @covers ::resolveRelativeTo
 * @covers \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath::getcwd
 */
final class UnixLikePathUnitTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!self::isWindows()) {
            return;
        }

        self::markTestSkipped('This test covers non-Windows functionality.');
    }

    /** @dataProvider providerBasicFunctionality */
    public function testBasicFunctionality(
        string $given,
        string $cwd,
        bool $isAbsolute,
        string $resolved,
        string $relativeBase,
        string $resolvedRelativeTo
    ): void {
        $sut = new UnixLikePath($given);
        $equalInstance = new UnixLikePath($given);
        $unequalInstance = new UnixLikePath(__DIR__);
        $relativeBase = new UnixLikePath($relativeBase);

        // Dynamically override CWD.
        $setCwd = function ($cwd) {
            /** @phpstan-ignore-next-line */
            $this->cwd = $cwd;
        };
        $setCwd->call($sut, $cwd);
        $setCwd->call($equalInstance, $cwd);

        self::assertEquals($resolved, $sut->resolve(), 'Got correct value via explicit method call.');

        self::assertEquals($isAbsolute, $sut->isAbsolute(), 'Correctly determined whether given path was relative.');
        self::assertEquals($given, $sut->raw(), 'Correctly returned raw path.');
        self::assertEquals($resolved, $sut->resolve(), 'Correctly resolved path.');
        self::assertEquals($resolvedRelativeTo, $sut->resolveRelativeTo($relativeBase), 'Correctly resolved path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($resolved, $sut->resolve(), 'Retained correct value after changing working directory.');
        self::assertEquals($sut, $equalInstance, 'Path value still considered equal to another instance with the same input after changing working directory.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            // Special CWD paths.
            'Path as empty string ()' => [
                'given' => '',
                'cwd' => '/var/one',
                'isAbsolute' => false,
                'resolved' => '/var/one',
                'relativeBase' => '/tmp/two',
                'resolvedRelativeTo' => '/tmp/two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'cwd' => '/var/three',
                'isAbsolute' => false,
                'resolved' => '/var/three',
                'relativeBase' => '/tmp/four',
                'resolvedRelativeTo' => '/tmp/four',
            ],
            'Path as dot-slash (./)' => [
                'given' => './',
                'cwd' => '/var/five',
                'isAbsolute' => false,
                'resolved' => '/var/five',
                'relativeBase' => '/tmp/six',
                'resolvedRelativeTo' => '/tmp/six',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'cwd' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'cwd' => '/var/two',
                'isAbsolute' => false,
                'resolved' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'resolvedRelativeTo' => '/tmp/three/ ',
            ],
            'Relative path with depth' => [
                'given' => 'one/two/three/four/five',
                'cwd' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'cwd' => '/var',
                'isAbsolute' => false,
                'resolved' => '/var/one/two',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'cwd' => '/var/four',
                'isAbsolute' => false,
                'resolved' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'resolvedRelativeTo' => '/tmp/five/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'cwd' => '/var/seven/eight',
                'isAbsolute' => false,
                'resolved' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'resolvedRelativeTo' => '/tmp/nine/two/five',
            ],
            'Relative path with leading double dots (..) and root path CWD' => [
                'given' => '../one/two',
                'cwd' => '/',
                'isAbsolute' => false,
                'resolved' => '/one/two',
                'relativeBase' => '/three/..',
                'resolvedRelativeTo' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root path CWD' => [
                'given' => '..',
                'cwd' => '/',
                'isAbsolute' => false,
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/seven/eight/nine/ten',
                'isAbsolute' => false,
                'resolved' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'resolvedRelativeTo' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'cwd' => '/',
                'isAbsolute' => true,
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'cwd' => '/var',
                'isAbsolute' => true,
                'resolved' => '/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/one',
            ],
            'Absolute path with depth' => [
                'given' => '/one/two/three/four/five',
                'cwd' => '/var/six/seven/eight/nine',
                'isAbsolute' => true,
                'resolved' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'resolvedRelativeTo' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/var/seven/eight/nine',
                'isAbsolute' => true,
                'resolved' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'resolvedRelativeTo' => '/one/six',
            ],
        ];
    }

    /** @dataProvider providerCwdArgument */
    public function testOptionalCwdArgument(string $path, ?PathInterface $cwd, string $resolved): void
    {
        $sut = new UnixLikePath($path, $cwd);

        self::assertEquals($resolved, $sut->resolve(), 'Correctly resolved path.');
    }

    public function providerCwdArgument(): array
    {
        return [
            'With $cwd argument.' => [
                'path' => 'one',
                'cwd' => new TestPath('/arg'),
                'resolved' => '/arg/one',
            ],
            'With explicit null $cwd argument' => [
                'path' => 'one',
                'cwd' => null,
                'resolved' => sprintf('%s/one', getcwd()),
            ],
        ];
    }
}
