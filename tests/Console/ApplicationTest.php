<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\ApplicationOptions;
use PhpTuf\ComposerStager\Tests\TestCase;

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
            ApplicationOptions::ACTIVE_DIR,
            ApplicationOptions::STAGING_DIR,
        ], array_keys($input->getOptions()), 'Set correct options');
    }

    /**
     * @dataProvider providerDefaultDirOptionDefinitions
     */
    public function testDefaultActiveDirOptionDefinitions($name, $shortcut): void
    {
        $application = new Application();
        $input = $application->getDefinition();
        $workingDirOption = $input->getOption($name);

        self::assertSame($name, $workingDirOption->getName(), 'Set correct name.');
        self::assertSame($shortcut, $workingDirOption->getShortcut(), 'Set correct shortcut.');
        self::assertNull($workingDirOption->getDefault(), 'Set correct default.');
        self::assertNotEmpty($workingDirOption->getDescription(), 'Set a description.');
    }

    public function providerDefaultDirOptionDefinitions(): array
    {
        return [
            [
                'name' => ApplicationOptions::ACTIVE_DIR,
                'shortcut' => 'd',
            ],
            [
                'name' => ApplicationOptions::STAGING_DIR,
                'shortcut' => 's',
            ],
        ];
    }
}
