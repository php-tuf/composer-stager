<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\ApplicationOptions;
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
 */
class ApplicationTest extends TestCase
{
    /**
     * @uses \PhpTuf\ComposerStager\Console\Application::getDefaultInputDefinition
     */
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
     * @covers ::getDefaultInputDefinition
     *
     * @dataProvider providerGlobalOptionDefinitions
     */
    public function testGlobalOptionDefinitions($name, $shortcut): void
    {
        $application = new Application();
        $input = $application->getDefinition();
        $option = $input->getOption($name);

        self::assertSame($name, $option->getName(), 'Set correct name.');
        self::assertSame(
            $shortcut,
            $option->getShortcut(),
            'Set correct shortcut.'
        );
        self::assertNull($option->getDefault(), 'Set correct default.');
        self::assertNotEmpty($option->getDescription(), 'Set a description.');
    }

    public function providerGlobalOptionDefinitions(): array
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

    /**
     * @covers ::doRunCommand
     */
    public function testGlobalOutputStyleOverrides(): void
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
        $foundCommand = $application->find($createdCommand->getName());
        $applicationTester = new ApplicationTester($application);
        $applicationTester->run([
            'command' => $foundCommand->getName(),
        ]);

        $expectedStyle = new OutputFormatterStyle('red');
        $actualStyle = $applicationTester->getOutput()
            ->getFormatter()
            ->getStyle('error');
        self::assertEquals($expectedStyle, $actualStyle, 'Overrode error style.');
    }
}
