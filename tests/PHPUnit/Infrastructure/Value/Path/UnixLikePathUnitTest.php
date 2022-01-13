<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @covers \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath::getcwd
 */
class UnixLikePathUnitTest extends TestCase
{
    /**
     * @covers ::__construct()
     * @covers ::getResolved
     * @covers ::getResolvedRelativeTo
     * @covers ::isAbsolute
     * @covers ::makeAbsolute
     * @covers ::normalize
     * @covers ::resolve
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($given, $cwd, $resolved, $relativeBase, $resolvedRelativeTo): void
    {
        // "Fix" directory separators on Windows systems so unit tests can
        // be run on them as smoke tests, if nothing else.
        if (self::isWindows()) {
            self::fixSeparatorsMultiple($given, $cwd, $resolved, $relativeBase, $resolvedRelativeTo);
        }

        $sut = new UnixLikePath($given);
        $equalInstance = new UnixLikePath($given);
        $unequalInstance = new UnixLikePath(__DIR__);
        $relativeBase = new UnixLikePath($relativeBase);

        // Dynamically override CWD.
        $setCwd = function ($cwd) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->cwd = $cwd;
        };
        $setCwd->call($sut, $cwd);
        $setCwd->call($equalInstance, $cwd);

        self::assertEquals($resolved, $sut->getResolved(), 'Got correct value via explicit method call.');

        chdir(__DIR__);

        self::assertEquals($resolved, $sut->getResolved(), 'Retained correct value after changing working directory.');

        self::assertEquals($resolved, $sut->getResolved(), 'Correctly resolved path.');
        self::assertEquals($resolvedRelativeTo, $sut->getResolvedRelativeTo($relativeBase), 'Correctly resolved path relative to another given path.');
        self::assertEquals($sut, $equalInstance, 'Path value considered equal to another instance with the same input.');
        self::assertNotEquals($sut, $unequalInstance, 'Path value considered unequal to another instance with different input.');

        // Make sure object is truly immutable.
        chdir(__DIR__);
        self::assertEquals($resolved, $sut->getResolved(), 'Retained correct value after changing working directory.');
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
                'resolved' => '/var/one',
                'relativeBase' => '/tmp/two',
                'resolvedRelativeTo' => '/tmp/two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'cwd' => '/var/three',
                'resolved' => '/var/three',
                'relativeBase' => '/tmp/four',
                'resolvedRelativeTo' => '/tmp/four',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'cwd' => '/var',
                'resolved' => '/var/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'cwd' => '/var/two',
                'resolved' => '/var/two/ ',
                'relativeBase' => '/tmp/three',
                'resolvedRelativeTo' => '/tmp/three/ ',
            ],
            'Relative path with nesting' => [
                'given' => 'one/two/three/four/five',
                'cwd' => '/var',
                'resolved' => '/var/one/two/three/four/five',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'cwd' => '/var',
                'resolved' => '/var/one/two',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/tmp/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'cwd' => '/var/four',
                'resolved' => '/var/four/one/two/three',
                'relativeBase' => '/tmp/five',
                'resolvedRelativeTo' => '/tmp/five/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'cwd' => '/var/seven/eight',
                'resolved' => '/var/seven/two/five',
                'relativeBase' => '/tmp/nine/ten',
                'resolvedRelativeTo' => '/tmp/nine/two/five',
            ],
            'Relative path with leading double dots (..) and root path CWD' => [
                'given' => '../one/two',
                'cwd' => '/',
                'resolved' => '/one/two',
                'relativeBase' => '/three/..',
                'resolvedRelativeTo' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root path CWD' => [
                'given' => '..',
                'cwd' => '/',
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/seven/eight/nine/ten',
                'resolved' => '/seven/eight/nine/ten/one/six',
                'relativeBase' => '/eleven/twelve/thirteen/fourteen',
                'resolvedRelativeTo' => '/eleven/twelve/thirteen/fourteen/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'cwd' => '/',
                'resolved' => '/',
                'relativeBase' => '/',
                'resolvedRelativeTo' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'cwd' => '/var',
                'resolved' => '/one',
                'relativeBase' => '/tmp',
                'resolvedRelativeTo' => '/one',
            ],
            'Absolute path with nesting' => [
                'given' => '/one/two/three/four/five',
                'cwd' => '/var/six/seven/eight/nine',
                'resolved' => '/one/two/three/four/five',
                'relativeBase' => '/tmp/ten/eleven/twelve/thirteen',
                'resolvedRelativeTo' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/var/seven/eight/nine',
                'resolved' => '/one/six',
                'relativeBase' => '/tmp/ten/eleven/twelve',
                'resolvedRelativeTo' => '/one/six',
            ],
        ];
    }
}
