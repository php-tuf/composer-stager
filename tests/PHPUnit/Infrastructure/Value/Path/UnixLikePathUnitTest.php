<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @covers ::__construct()
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class UnixLikePathUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    private function createSut($path): UnixLikePath
    {
        $filesystem = $this->filesystem->reveal();
        return new UnixLikePath($filesystem, $path);
    }

    /**
     * @covers ::__toString
     * @covers ::getAbsolute
     * @covers ::isAbsolute
     * @covers ::makeAbsolute
     * @covers ::normalize
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($given, $cwd, $resolved): void
    {
        self::fixSeparatorsMultiple($given, $cwd, $resolved);

        $this->filesystem
            ->getcwd()
            ->willReturn($cwd);

        $sut = $this->createSut($given);

        self::assertEquals($resolved, $sut->getAbsolute());
        self::assertEquals($resolved, $sut);
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
