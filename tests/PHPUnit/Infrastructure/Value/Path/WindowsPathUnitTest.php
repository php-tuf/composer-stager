<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path;

use PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 */
class WindowsPathUnitTest extends TestCase
{
    /**
     * @covers ::__construct()
     * @covers ::__toString
     * @covers ::getAbsolute
     * @covers ::getAbsoluteFromRelative
     * @covers ::getcwd
     * @covers ::isAbsoluteFromCurrentDrive
     * @covers ::isAbsoluteFromSpecificDrive
     * @covers ::normalize
     * @covers ::normalizeAbsoluteFromCurrentDrive
     * @covers ::normalizeAbsoluteFromSpecificDrive
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality($given, $cwd, $absolute): void
    {
        // "Fix" directory separators on non-Windows systems so unit tests can
        // be run on them as smoke tests, if nothing else.
        if (!self::isWindows()) {
            self::fixSeparatorsMultiple($given, $cwd, $absolute);
        }

        $sut = new WindowsPath($given);

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
                'cwd' => 'C:\\Program Files\\One',
                'absolute' => 'C:\\Program Files\\One',
            ],
            'Path as dot (.)' => [
                'given' => '.',
                'cwd' => 'C:\\Program Files\\Three',
                'absolute' => 'C:\\Program Files\\Three',
            ],
            // Relative paths.
            'Relative path as simple string' => [
                'given' => 'One',
                'cwd' => 'C:\\Program Files',
                'absolute' => 'C:\\Program Files\\One',
            ],
            'Relative path as space ( )' => [
                'given' => ' ',
                'cwd' => 'C:\\Program Files\\Two',
                'absolute' => 'C:\\Program Files\\Two\\ ',
            ],
            'Relative path with nesting' => [
                'given' => 'One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Program Files',
                'absolute' => 'C:\\Program Files\\One\\Two\\Three\\Four\\Five',
            ],
            'Relative path with trailing slash' => [
                'given' => 'One\\Two\\',
                'cwd' => 'C:\\Program Files',
                'absolute' => 'C:\\Program Files\\One\\Two',
            ],
            'Relative path with repeating directory separators' => [
                'given' => 'One\\\\Two\\\\\\\\Three',
                'cwd' => 'C:\\Program Files\\Four',
                'absolute' => 'C:\\Program Files\\Four\\One\\Two\\Three',
            ],
            'Relative path with double dots (..)' => [
                'given' => '..\\One\\..\\Two\\Three\\Four\\..\\..\\Five\\Six\\..',
                'cwd' => 'C:\\Program Files\\Seven\\Eight',
                'absolute' => 'C:\\Program Files\\Seven\\Two\\Five',
            ],
            'Relative path with leading double dots (..) and root path CWD' => [
                'given' => '..\\One\\Two',
                'cwd' => 'C:\\',
                'absolute' => 'C:\\One\\Two',
            ],
            'Silly combination of relative path as double dots (..) with root path CWD' => [
                'given' => '..',
                'cwd' => 'C:\\',
                'absolute' => 'C:\\',
            ],
            'Crazy relative path' => [
                'given' => 'One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'absolute' => 'C:\\Seven\\Eight\\Nine\\Ten\\One\\Six',
            ],
            // Absolute paths from the root of a specific drive.
            'Absolute path to the root of a specific drive' => [
                'given' => 'D:\\',
                'cwd' => 'C:\\',
                'absolute' => 'D:\\',
            ],
            'Absolute path from the root of a specific drive as simple string' => [
                'given' => 'D:\\One',
                'cwd' => 'C:\\Program Files',
                'absolute' => 'D:\\One',
            ],
            'Absolute path from the root of a specific drive with nesting' => [
                'given' => 'D:\\One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Program Files\\Six\\Seven\\Eight\\Nine',
                'absolute' => 'D:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of a specific drive' => [
                'given' => 'D:\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'absolute' => 'D:\\One\\Six',
            ],
            // Absolute paths from the root of the current drive.
            'Absolute path to the root of the current drive' => [
                'given' => '\\',
                'cwd' => 'C:\\',
                'absolute' => 'C:\\',
            ],
            'Absolute path from the root of the current drive as a simple string' => [
                'given' => '\\One',
                'cwd' => 'C:\\Program Files',
                'absolute' => 'C:\\One',
            ],
            'Absolute path from the root of the current drive with nesting' => [
                'given' => '\\One\\Two\\Three\\Four\\Five',
                'cwd' => 'C:\\Program Files\\Six\\Seven\\Eight\\Nine',
                'absolute' => 'C:\\One\\Two\\Three\\Four\\Five',
            ],
            'Crazy absolute path from the root of the current drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'absolute' => 'C:\\One\\Six',
            ],
            'Crazy absolute path from the root of a specified drive' => [
                'given' => '\\One\\.\\\\\\\\.\\Two\\Three\\Four\\Five\\.\\.\\.\\..\\\\.\\\\..\\\\\\\\\\..\\.\\.\\..\\.\\Six\\\\\\\\\\',
                'cwd' => 'C:\\Seven\\Eight\\Nine\\Ten',
                'absolute' => 'C:\\One\\Six',
            ],
        ];
    }
}
