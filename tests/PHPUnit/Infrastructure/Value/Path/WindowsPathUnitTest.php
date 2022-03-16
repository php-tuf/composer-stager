<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath */
class WindowsPathUnitTest extends TestCase
{
    /**
     * @covers ::__construct()
     * @covers ::doResolve
     * @covers ::getAbsoluteFromRelative
     * @covers ::isAbsoluteFromCurrentDrive
     * @covers ::isAbsoluteFromSpecificDrive
     * @covers ::normalize
     * @covers ::normalizeAbsoluteFromCurrentDrive
     * @covers ::normalizeAbsoluteFromSpecificDrive
     * @covers ::resolve
     * @covers ::resolveRelativeTo
     * @covers \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath::getcwd
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($given, $cwd, $resolved, $relativeBase, $resolvedRelativeTo): void
    {
        // "Fix" directory separators on non-Windows systems so unit tests can
        // be run on them as smoke tests, if nothing else.
        if (!self::isWindows()) {
            self::fixSeparatorsMultiple($given, $cwd, $resolved, $relativeBase, $resolvedRelativeTo);
        }

        $sut = new WindowsPath($given);
        $equalInstance = new WindowsPath($given);
        $unequalInstance = new WindowsPath(__DIR__);
        $relativeBase = new WindowsPath($relativeBase);

        // Dynamically override CWD.
        $setCwd = function ($cwd) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->cwd = $cwd;
        };
        $setCwd->call($sut, $cwd);
        $setCwd->call($equalInstance, $cwd);

        self::assertEquals($resolved, $sut->resolve(), 'Got correct value via explicit method call.');

        chdir(__DIR__);

        self::assertEquals($resolved, $sut->resolve(), 'Retained correct value after changing working directory.');

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
                'cwd' => 'C:\\Windows\\One',
                'resolved' => 'C:\\Windows\\One',
                'relativeBase' => 'D:\\Users\\Two',
                'resolvedRelativeTo' => 'D:\\Users\\Two',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'cwd' => 'C:\\Windows\\Three',
                'resolved' => 'C:\\Windows\\Three',
                'relativeBase' => 'D:\\Users\\Four',
                'resolvedRelativeTo' => 'D:\\Users\\Four',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'One',
                'cwd' => 'C:\\Windows',
                'resolved' => 'C:\\Windows\\One',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\Users\\One',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'cwd' => 'C:\\Windows\\Two',
                'resolved' => 'C:\\Windows\\Two\\ ',
                'relativeBase' => 'D:\\Users\\Three',
                'resolvedRelativeTo' => 'D:\\Users\\Three\\ ',
            ],
            'Relative path with nesting' => [
                'given' => 'One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Windows',
                'resolved' => 'C:\\Windows\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\Users\\One\\Two\\Three\\Four\\Five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'One\\Two\\',
                'cwd' => 'C:\\Windows',
                'resolved' => 'C:\\Windows\\One\\Two',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\Users\\One\\Two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'One\\\\Two\\\\\\\\Three',
                'cwd' => 'C:\\Windows\\Four',
                'resolved' => 'C:\\Windows\\Four\\One\\Two\\Three',
                'relativeBase' => 'D:\\Users\\Five',
                'resolvedRelativeTo' => 'D:\\Users\\Five\\One\\Two\\Three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '..\\One\\..\\Two\\Three\\Four\\..\\..\\Five\\Six\\..',
                'cwd' => 'C:\\Windows\\Seven\\Eight',
                'resolved' => 'C:\\Windows\\Seven\\Two\\Five',
                'relativeBase' => 'D:\\Users\\Nine\\Ten',
                'resolvedRelativeTo' => 'D:\\Users\\Nine\\Two\\Five',
            ],
            'Relative path with leading double dots (..) and root path CWD' => [
                'given' => '..\\One\\Two',
                'cwd' => 'C:\\',
                'resolved' => 'C:\\One\\Two',
                'relativeBase' => 'D:\\',
                'resolvedRelativeTo' => 'D:\\One\\Two',
            ],
            'Silly combination of relative path as double dots (..) with root path CWD' => [
                'given' => '..',
                'cwd' => 'C:\\',
                'resolved' => 'C:\\',
                'relativeBase' => 'D:\\',
                'resolvedRelativeTo' => 'D:\\',
            ],
            'Crazy relative path' => [
                'given' => 'One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'resolved' => 'C:\\Seven\\Eight\\Nine\\Ten\\One\\Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Thirteen\\Fourteen',
                'resolvedRelativeTo' => 'D:\\Eleven\\Twelve\\Thirteen\\Fourteen\\One\\Six',
            ],
            // Absolute paths from the root of a specific drive.
            'Absolute path to the root of a specific drive' => [
                'given' => 'D:\\',
                'cwd' => 'C:\\',
                'resolved' => 'D:\\',
                'relativeBase' => 'D:\\',
                'resolvedRelativeTo' => 'D:\\',
            ],
            'Absolute path from the root of a specific drive as simple string' => [
                'given' => 'D:\\One',
                'cwd' => 'C:\\Windows',
                'resolved' => 'D:\\One',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One',
            ],
            'Absolute path from the root of a specific drive with nesting' => [
                'given' => 'D:\\One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Windows\\Six\\Seven\\Eight\\Nine',
                'resolved' => 'D:\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of a specific drive' => [
                'given' => 'D:\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'resolved' => 'D:\\One\\Six',
                'relativeBase' => 'D:\\Eleven\\Twelve\\Fourteen',
                'resolvedRelativeTo' => 'D:\\One\\Six',
            ],
            // Absolute paths from the root of the current drive.
            'Absolute path to the root of the current drive' => [
                'given' => '\\',
                'cwd' => 'C:\\',
                'resolved' => 'C:\\',
                'relativeBase' => 'C:\\',
                'resolvedRelativeTo' => 'C:\\',
            ],
            'Absolute path from the root of the current drive as a simple string' => [
                'given' => '\\One',
                'cwd' => 'C:\\Windows',
                'resolved' => 'C:\\One',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One',
            ],
            'Absolute path from the root of the current drive with nesting' => [
                'given' => '\\One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Windows\\Six\\Seven\\Eight\\Nine',
                'resolved' => 'C:\\One\\Two\\Three\\Four\\Five',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of the current drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'resolved' => 'C:\\One\\Six',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One\\Six',
            ],
            'Crazy absolute path from the root of a specified drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'resolved' => 'C:\\One\\Six',
                'relativeBase' => 'D:\\Users',
                'resolvedRelativeTo' => 'D:\\One\\Six',
            ],
        ];
    }
}
