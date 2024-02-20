<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

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
 * @covers ::isRelative
 * @covers ::relative
 * @covers ::stripProtocol
 *
 * @group no_windows
 */
final class UnixLikePathUnitTest extends PathUnitTestCase
{
    public function providerBasicFunctionality(): array
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

    public function providerBaseDirArgument(): array
    {
        return [
            'With $basePath argument.' => [
                'path' => 'one',
                'baseDir' => PathHelper::createPath('/arg'),
                'absolute' => '/arg/one',
            ],
            'With explicit null $basePath argument' => [
                'path' => 'one',
                'baseDir' => null,
                'absolute' => sprintf('%s/one', getcwd()),
            ],
        ];
    }
}
