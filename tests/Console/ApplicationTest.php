<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\Application;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Application
 * @covers \PhpTuf\ComposerStager\Console\Application::__construct
 * @covers \PhpTuf\ComposerStager\Console\Application::getDefaultInputDefinition
 */
class ApplicationTest extends TestCase
{
    public function testDefaultOptions(): void
    {
        $application = new Application();
        $input = $application->getDefinition();

        self::assertSame([
            'help',
            'quiet',
            'verbose',
            'version',
            'ansi',
            'no-ansi',
            'no-interaction',
            'active-dir',
            'staging-dir',
        ], array_keys($input->getOptions()), 'Set correct options');
    }

    public function testDefaultActiveDirOption(): void
    {
        $application = new Application();
        $input = $application->getDefinition();
        $workingDirOption = $input->getOption('active-dir');

        self::assertSame('active-dir', $workingDirOption->getName(), 'Set correct name.');
        self::assertSame('d', $workingDirOption->getShortcut(), 'Set correct shortcut.');
        self::assertNull($workingDirOption->getDefault(), 'Set correct default.');
        self::assertNotEmpty($workingDirOption->getDescription(), 'Set a description.');
    }

    public function testDefaultStagingDirOption(): void
    {
        $application = new Application();
        $input = $application->getDefinition();
        $workingDirOption = $input->getOption('staging-dir');

        self::assertSame('staging-dir', $workingDirOption->getName(), 'Set correct name.');
        self::assertSame('s', $workingDirOption->getShortcut(), 'Set correct shortcut.');
        self::assertNull($workingDirOption->getDefault(), 'Set correct default.');
        self::assertNotEmpty($workingDirOption->getDescription(), 'Set a description.');
    }
}
