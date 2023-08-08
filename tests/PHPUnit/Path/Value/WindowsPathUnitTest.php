<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Path\Value;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 *
 * @covers ::__construct
 * @covers ::absolute
 * @covers ::doAbsolute
 * @covers ::getAbsoluteFromRelative
 * @covers ::isAbsolute
 * @covers ::isAbsoluteFromCurrentDrive
 * @covers ::isAbsoluteFromSpecificDrive
 * @covers ::normalize
 * @covers ::normalizeAbsoluteFromCurrentDrive
 * @covers ::normalizeAbsoluteFromSpecificDrive
 * @covers ::relative
 * @covers \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath::getcwd
 */
final class WindowsPathUnitTest extends TestCase
{
    public string $basePath;

    /** @dataProvider providerBasicFunctionality */
    public function testBasicFunctionality(
        string $given,
        string $basePath,
        bool $isAbsolute,
        string $absolute,
        string $relativeBase,
        string $relative,
    ): void {
        self::fixSeparatorsMultiple($given, $basePath, $absolute, $relativeBase, $relative);

        $sut = new WindowsPath($given);
        $equalInstance = new WindowsPath($given);
        $unequalInstance = new WindowsPath(__DIR__);
        $relativeBase = new WindowsPath($relativeBase);

        // Dynamically override base path.
        $setBaseDir = function ($basePath): void {
            $this->basePath = $basePath;
        };
        $setBaseDir->call($sut, $basePath);
        $setBaseDir->call($equalInstance, $basePath);

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
        return [
            // Special base path paths.
            'Path as empty string ()' => [
                'given' => '',
                'baseDir' => 'C:\\Windows\\One',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\One',
                'relativeBase' => 'D:\\Users\\Two',
                'relative' => 'D:\\Users\\Two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'baseDir' => 'C:\\Windows\\Three',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\Three',
                'relativeBase' => 'D:\\Users\\Four',
                'relative' => 'D:\\Users\\Four',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'One',
                'baseDir' => 'C:\\Windows',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\One',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\Users\\One',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'baseDir' => 'C:\\Windows\\Two',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\Two\\ ',
                'relativeBase' => 'D:\\Users\\Three',
                'relative' => 'D:\\Users\\Three\\ ',
            ],
            'Relative path with nesting' => [
                'given' => 'One\\Two\\Three\\Four\\Five',
                'baseDir' => 'C:\\Windows',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\Users\\One\\Two\\Three\\Four\\Five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'One\\Two\\',
                'baseDir' => 'C:\\Windows',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\One\\Two',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\Users\\One\\Two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'One\\\\Two\\\\\\\\Three',
                'baseDir' => 'C:\\Windows\\Four',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\Four\\One\\Two\\Three',
                'relativeBase' => 'D:\\Users\\Five',
                'relative' => 'D:\\Users\\Five\\One\\Two\\Three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '..\\One\\..\\Two\\Three\\Four\\..\\..\\Five\\Six\\..',
                'baseDir' => 'C:\\Windows\\Seven\\Eight',
                'isAbsolute' => false,
                'absolute' => 'C:\\Windows\\Seven\\Two\\Five',
                'relativeBase' => 'D:\\Users\\Nine\\Ten',
                'relative' => 'D:\\Users\\Nine\\Two\\Five',
            ],
            'Relative path with leading double dots (..) and root path base path' => [
                'given' => '..\\One\\Two',
                'baseDir' => 'C:\\',
                'isAbsolute' => false,
                'absolute' => 'C:\\One\\Two',
                'relativeBase' => 'D:\\',
                'relative' => 'D:\\One\\Two',
            ],
            'Silly combination of relative path as double dots (..) with root path base path' => [
                'given' => '..',
                'baseDir' => 'C:\\',
                'isAbsolute' => false,
                'absolute' => 'C:\\',
                'relativeBase' => 'D:\\',
                'relative' => 'D:\\',
            ],
            'Crazy relative path' => [
                'given' => 'One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'baseDir' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'isAbsolute' => false,
                'absolute' => 'C:\\Seven\\Eight\\Nine\\Ten\\One\\Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Thirteen\\Fourteen',
                'relative' => 'D:\\Eleven\\Twelve\\Thirteen\\Fourteen\\One\\Six',
            ],
            // Absolute paths from the root of a specific drive.
            'Absolute path to the root of a specific drive' => [
                'given' => 'D:\\',
                'baseDir' => 'C:\\',
                'isAbsolute' => true,
                'absolute' => 'D:\\',
                'relativeBase' => 'D:\\',
                'relative' => 'D:\\',
            ],
            'Absolute path from the root of a specific drive as simple string' => [
                'given' => 'D:\\One',
                'baseDir' => 'C:\\Windows',
                'isAbsolute' => true,
                'absolute' => 'D:\\One',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One',
            ],
            'Absolute path from the root of a specific drive with nesting' => [
                'given' => 'D:\\One\\Two\\Three\\Four\\Five',
                'baseDir' => 'C:\\Windows\\Six\\Seven\\Eight\\Nine',
                'isAbsolute' => true,
                'absolute' => 'D:\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of a specific drive' => [
                'given' => 'D:\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'baseDir' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'isAbsolute' => true,
                'absolute' => 'D:\\One\\Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Fourteen',
                'relative' => 'D:\\One\\Six',
            ],
            // Absolute paths from the root of the current drive.
            'Absolute path to the root of the current drive' => [
                'given' => '\\',
                'baseDir' => 'C:\\',
                'isAbsolute' => true,
                'absolute' => 'C:\\',
                'relativeBase' => 'C:\\',
                'relative' => 'C:\\',
            ],
            'Absolute path from the root of the current drive as a simple string' => [
                'given' => '\\One',
                'baseDir' => 'C:\\Windows',
                'isAbsolute' => true,
                'absolute' => 'C:\\One',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One',
            ],
            'Absolute path from the root of the current drive with nesting' => [
                'given' => '\\One\\Two\\Three\\Four\\Five',
                'baseDir' => 'C:\\Windows\\Six\\Seven\\Eight\\Nine',
                'isAbsolute' => true,
                'absolute' => 'C:\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of the current drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'baseDir' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'isAbsolute' => true,
                'absolute' => 'C:\\One\\Six',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One\\Six',
            ],
            'Crazy absolute path from the root of a specified drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'baseDir' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'isAbsolute' => true,
                'absolute' => 'C:\\One\\Six',
                'relativeBase' => 'D:\\Users',
                'relative' => 'D:\\One\\Six',
            ],
        ];
    }

    /**
     * @dataProvider providerBaseDirArgument
     *
     * @group windows_only
     *   This test doesn't work well on non-Windows systems, owing to its dependence on getcwd().
     */
    public function testOptionalBaseDirArgument(string $path, ?PathInterface $basePath, string $absolute): void
    {
        $sut = new WindowsPath($path, $basePath);

        self::assertEquals($absolute, $sut->absolute(), 'Got absolute path.');
    }

    public function providerBaseDirArgument(): array
    {
        return [
            'With $basePath argument.' => [
                'path' => 'One',
                'baseDir' => new TestPath('C:\\Arg'),
                'absolute' => 'C:\\Arg\\One',
            ],
            'With explicit null $basePath argument' => [
                'path' => 'One',
                'baseDir' => null,
                'absolute' => sprintf('%s\\One', getcwd()),
            ],
        ];
    }
}
