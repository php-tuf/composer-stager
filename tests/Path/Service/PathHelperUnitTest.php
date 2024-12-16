<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Service;

use PhpTuf\ComposerStager\Internal\Path\Service\PathHelper;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PathHelper::class)]
final class PathHelperUnitTest extends TestCase
{
    public function createSut(): PathHelper
    {
        return new PathHelper();
    }

    #[DataProvider('providerCanonicalize')]
    public function testCanonicalize(string $unixLike, string $windows, string $expected): void
    {
        $sut = $this->createSut();

        $actualUnixLike = $sut->canonicalize($unixLike);
        $actualWindows = $sut->canonicalize($windows);

        self::assertSame($expected, $actualUnixLike, 'Correctly canonicalized Unix-like path.');
        self::assertSame($expected, $actualWindows, 'Correctly canonicalized Windows path.');
    }

    public static function providerCanonicalize(): array
    {
        return [
            'Empty paths' => [
                'unixLike' => '',
                'windows' => '',
                'expected' => '',
            ],
            'Single dot' => [
                'unixLike' => '.',
                'windows' => '.',
                'expected' => '',
            ],
            'Dot slash' => [
                'unixLike' => './',
                'windows' => '.\\',
                'expected' => '',
            ],
            'Simple path' => [
                'unixLike' => 'one',
                'windows' => 'one',
                'expected' => 'one',
            ],
            'Simple path with depth' => [
                'unixLike' => 'one/two/three/four/five',
                'windows' => 'one\\two\\three\\four\\five',
                'expected' => 'one/two/three/four/five',
            ],
            'Crazy relative path' => [
                'unixLike' => 'one/.////./two/three/four/five/./././..//.//../////../././.././six/////',
                'windows' => 'one\\.\\\\\\\\.\\two\\three\\four\\five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\six\\\\\\\\\\',
                'expected' => 'one/six',
            ],
            'Unix-like absolute path' => [
                'unixLike' => '/',
                'windows' => '\\', // This is actually a legitimate UNC path on Windows: @see https://learn.microsoft.com/en-us/dotnet/standard/io/file-path-formats#unc-paths
                'expected' => '/',
            ],
            'Windows drive name' => [
                'unixLike' => 'C:/', // This would be an absurd Unix-like path, of course, but it's still testable. Same below.
                'windows' => 'C:\\',
                'expected' => 'C:/',
            ],
            'Windows drive name no slash' => [
                'unixLike' => 'C:',
                'windows' => 'C:',
                'expected' => 'C:/',
            ],
            'Windows drive name with extra slashes' => [
                'unixLike' => 'C:///',
                'windows' => 'C:\\\\\\',
                'expected' => 'C:/',
            ],
            'Absolute Windows path with extra slashes' => [
                'unixLike' => 'C:////one',
                'windows' => 'C:\\\\\\\\one',
                'expected' => 'C:/one',
            ],
        ];
    }

    #[DataProvider('providerAbsoluteRelative')]
    public function testAbsoluteRelative(bool $isAbsolute, string $path): void
    {
        $sut = $this->createSut();

        self::assertSame($isAbsolute, $sut->isAbsolute($path));
        self::assertSame(!$isAbsolute, $sut->isRelative($path));
    }

    public static function providerAbsoluteRelative(): array
    {
        return [
            // Yes.
            'True: Unix' => [true, '/one/two'],
            'True: Windows' => [true, 'C:\\One\\Two'],
            'True: UNC' => [true, '\\One\\Two'],
            // No.
            'False: Unix' => [false, 'one/two'],
            'False: Windows' => [false, '../one/two'],
            'False: UNC' => [false, '..\\One\\Two'],
        ];
    }

    #[DataProvider('providerIsRelative')]
    public function testIsRelative(string $descendant, string $ancestor, bool $isDescendant): void
    {
        $sut = $this->createSut();

        $actualIsDescendant = $sut->isDescendant($descendant, $ancestor);

        self::assertSame($isDescendant, $actualIsDescendant);
    }

    public static function providerIsRelative(): array
    {
        return [
            'Simple descendant' => [
                'descendant' => '/one/two',
                'ancestor' => '/one',
                'isDescendant' => true,
            ],
            'With depth' => [
                'descendant' => '/one/two/three/four/five',
                'ancestor' => '/one/two/three',
                'isDescendant' => true,
            ],
            'Empty values' => [
                'descendant' => '',
                'ancestor' => '',
                'isDescendant' => false,
            ],
            'Identical paths' => [
                'descendant' => '/one/two/three',
                'ancestor' => '/one/two/three',
                'isDescendant' => false,
            ],
            'Relative descendant' => [
                'descendant' => 'one/two/three',
                'ancestor' => '/four/five/six',
                'isDescendant' => false,
            ],
            'Relative ancestor' => [
                'descendant' => '/one/two/three',
                'ancestor' => 'one/two/three',
                'isDescendant' => false,
            ],
            'Sneaky "near match"' => [
                'descendant' => '/one_two',
                'ancestor' => '/one',
                'isDescendant' => false,
            ],
        ];
    }
}
