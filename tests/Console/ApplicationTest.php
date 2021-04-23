<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\GlobalOptions;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Application
 * @covers \PhpTuf\ComposerStager\Console\Application::__construct
 * @covers \PhpTuf\ComposerStager\Console\Application::getDefaultInputDefinition
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\GlobalOptions
 */
class ApplicationTest extends TestCase
{
    private const TEST_COMMAND = ['command' => 'test'];

    private function createSut(): Application
    {
        $createdCommand = new class() extends Command {
            protected static $defaultName = 'test';

            protected function execute(
                InputInterface $input,
                OutputInterface $output
            ): int {
                return ExitCode::SUCCESS;
            }
        };

        $application = new Application();
        $application->setAutoExit(false);
        $application->add($createdCommand);
        return $application;
    }

    /**
     * @uses \PhpTuf\ComposerStager\Console\Application::getDefaultInputDefinition
     */
    public function testDefaultOptions(): void
    {
        $application = $this->createSut();
        $input = $application->getDefinition();

        self::assertSame([
            'help',
            'quiet',
            'verbose',
            'version',
            'ansi',
            'no-ansi',
            'no-interaction',

            GlobalOptions::ACTIVE_DIR,
            GlobalOptions::STAGING_DIR,

        ], array_keys($input->getOptionDefaults()), 'Set correct options');
    }

    /**
     * @covers ::getDefaultInputDefinition
     *
     * @dataProvider providerGlobalOptionDefinitions
     */
    public function testGlobalOptionDefinitions($name, $descriptionContains, $shortcut): void
    {
        $application = $this->createSut();
        $input = $application->getDefinition();
        $option = $input->getOption($name);

        self::assertSame($name, $option->getName(), 'Set correct name.');
        self::assertSame($shortcut, $option->getShortcut(), 'Set correct shortcut.');
        self::assertNull($option->getDefault(), 'Set correct default.');
        self::assertStringContainsString($descriptionContains, $option->getDescription(), 'Set correct description.');
    }

    public function providerGlobalOptionDefinitions(): array
    {
        return [
            [
                'name' => GlobalOptions::ACTIVE_DIR,
                'descriptionContains' => 'active',
                'shortcut' => 'd',
            ],
            [
                'name' => GlobalOptions::STAGING_DIR,
                'descriptionContains' => 'staging',
                'shortcut' => 's',
            ],
        ];
    }

    /**
     * @covers ::configureIO
     */
    public function testGlobalOutputStyleOverrides(): void
    {
        $application = $this->createSut();
        $applicationTester = new ApplicationTester($application);

        $applicationTester->run(static::TEST_COMMAND);

        $expectedStyle = new OutputFormatterStyle('red');
        $actualStyle = $applicationTester->getOutput()
            ->getFormatter()
            ->getStyle('error');

        self::assertEquals($expectedStyle, $actualStyle, 'Overrode error style.');
    }
}
