<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 */
class UnixLikePathUnitTest extends TestCase
{
    /**
     * @covers ::__construct()
     * @covers ::__toString
     * @covers ::getAbsolute
     * @covers ::getcwd
     * @covers ::isAbsolute
     * @covers ::makeAbsolute
     * @covers ::normalize
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($given, $cwd, $absolute): void
    {
        self::fixSeparatorsMultiple($given, $cwd, $absolute);

        $sut = new UnixLikePath($given);

        // Dynamically override CWD.
        $setCwd = function ($cwd) {
            $this->cwd = $cwd;
        };
        $setCwd->call($sut, $cwd);

        self::assertEquals($absolute, $sut->getAbsolute(), 'Got correct value via explicit method call.');
        self::assertEquals($absolute, $sut, 'Got correct value by implicit casting to string.');

        chdir(__DIR__);

        self::assertEquals($absolute, $sut->getAbsolute(), 'Retained correct value after changing working directory.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            // Special CWD paths.
            'Path as empty string ()' => [
                'given' => '',
                'cwd' => '/var/one',
                'absolute' => '/var/one',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'cwd' => '/var/three',
                'absolute' => '/var/three',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'one',
                'cwd' => '/var',
                'absolute' => '/var/one',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'cwd' => '/var/two',
                'absolute' => '/var/two/ ',
            ],
            'Relative path with nesting' => [
                'given' => 'one/two/three/four/five',
                'cwd' => '/var',
                'absolute' => '/var/one/two/three/four/five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'one/two/',
                'cwd' => '/var',
                'absolute' => '/var/one/two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'one//two////three',
                'cwd' => '/var/four',
                'absolute' => '/var/four/one/two/three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '../one/../two/three/four/../../five/six/..',
                'cwd' => '/var/seven/eight',
                'absolute' => '/var/seven/two/five',
            ],
            'Relative path with leading double dots (..) and root path CWD' => [
                'given' => '../one/two',
                'cwd' => '/',
                'absolute' => '/one/two',
            ],
            'Silly combination of relative path as double dots (..) with root path CWD' => [
                'given' => '..',
                'cwd' => '/',
                'absolute' => '/',
            ],
            'Crazy relative path' => [
                'given' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/seven/eight/nine/ten',
                'absolute' => '/seven/eight/nine/ten/one/six',
            ],
            // Absolute paths.
            'Absolute path to the root' => [
                'given' => '/',
                'cwd' => '/',
                'absolute' => '/',
            ],
            'Absolute path as simple string' => [
                'given' => '/one',
                'cwd' => '/var',
                'absolute' => '/one',
            ],
            'Absolute path with nesting' => [
                'given' => '/one/two/three/four/five',
                'cwd' => '/var/six/seven/eight/nine',
                'absolute' => '/one/two/three/four/five',
            ],
            'Crazy absolute path' => [
                'given' => '/one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'cwd' => '/seven/eight/nine/ten',
                'absolute' => '/one/six',
            ],
        ];
    }
}
