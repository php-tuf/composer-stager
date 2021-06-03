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
    use GlobalOptionsSetupTrait;

    private const TEST_COMMAND = ['command' => 'test'];

    protected function setUp(): void
    {
        $this->setUpGlobalOptions();
    }

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

        /** @var GlobalOptions $globalOptions */
        $globalOptions = $this->globalOptions->reveal();
        $application = new Application($globalOptions);
        $application->setAutoExit(false);
        $application->add($createdCommand);
        return $application;
    }

    /**
     * @uses \PhpTuf\ComposerStager\Console\Application::getDefaultInputDefinition
     */
    public function testDefaultOptions(): void
    {
        $baseOptions = (new \Symfony\Component\Console\Application())
            ->getDefinition()
            ->getOptionDefaults();
        $sutOptions = $this->createSut()
            ->getDefinition()
            ->getOptionDefaults();

        $addedOptions = array_diff_key($sutOptions, $baseOptions);

        self::assertSame([
            GlobalOptions::ACTIVE_DIR,
            GlobalOptions::STAGING_DIR,
        ], array_keys($addedOptions), 'Set correct options');
    }

    /**
     * @covers ::getDefaultInputDefinition
     *
     * @dataProvider providerGlobalOptionDefinitions
     */
    public function testGlobalOptionDefinitions(
        $name,
        $descriptionContains,
        $shortcut,
        $default
    ): void {
        $this->globalOptions
            ->getDefaultActiveDir()
            ->willReturn($default);
        $this->globalOptions
            ->getDefaultStagingDir()
            ->willReturn($default);
        $application = $this->createSut();
        $input = $application->getDefinition();
        $option = $input->getOption($name);

        self::assertSame($name, $option->getName(), 'Set correct name.');
        self::assertSame($shortcut, $option->getShortcut(), 'Set correct shortcut.');
        self::assertSame($default, $option->getDefault(), 'Set correct default.');
        self::assertStringContainsString($descriptionContains, $option->getDescription(), 'Set correct description.');
    }

    public function providerGlobalOptionDefinitions(): array
    {
        return [
            [
                'name' => GlobalOptions::ACTIVE_DIR,
                'descriptionContains' => 'active',
                'shortcut' => 'd',
                'default' => '/lorem',
            ],
            [
                'name' => GlobalOptions::STAGING_DIR,
                'descriptionContains' => 'staging',
                'shortcut' => 's',
                'default' => '/ipsum',
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
