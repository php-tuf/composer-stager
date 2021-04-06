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
                'name' => 'active-dir',
                'shortcut' => 'd',
            ],
            [
                'name' => 'staging-dir',
                'shortcut' => 's',
            ],
        ];
    }
}
