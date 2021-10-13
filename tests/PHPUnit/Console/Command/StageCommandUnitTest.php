<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Command\AbstractCommand;
use PhpTuf\ComposerStager\Console\Command\StageCommand;
use PhpTuf\ComposerStager\Domain\StagerInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Tests\PHPUnit\Console\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\StageCommand
 * @covers \PhpTuf\ComposerStager\Console\Command\StageCommand::__construct
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\StageCommand
 * @uses \PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback
 *
 * @property \PhpTuf\ComposerStager\Domain\Stager|\Prophecy\Prophecy\ObjectProphecy stager
 */
class StageCommandUnitTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->stager = $this->prophesize(StagerInterface::class);
        parent::setUp();
    }

    protected function createSut(): Command
    {
        $stager = $this->stager->reveal();
        return new StageCommand($stager);
    }

    /**
     * @covers ::configure
     */
    public function testBasicConfiguration(): void
    {
        $command = $this->createSut();

        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();
        $composerCommandArgument = $definition->getArgument('composer-command');

        self::assertSame('stage', $command->getName(), 'Set correct name.');
        self::assertSame([], $command->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($command->getDescription(), 'Set a description.');
        self::assertSame(['composer-command'], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
        self::assertNotEmpty($command->getUsages(), 'Set usages.');
        self::assertNotEmpty($command->getHelp(), 'Set help.');

        self::assertTrue($composerCommandArgument->isRequired(), 'Required Composer command option.');
        self::assertTrue($composerCommandArgument->isArray(), 'Set Composer command to array.');
        self::assertNotEmpty($composerCommandArgument->getDescription(), "Description provided.");
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerBasicExecution
     */
    public function testBasicExecution($composerCommand, $stagingDir): void
    {
        $this->stager
            ->stage($composerCommand, $stagingDir, Argument::any())
            ->shouldBeCalledOnce();

        $this->executeCommand([
            'composer-command' => $composerCommand,
            sprintf('--%s', Application::STAGING_DIR_OPTION) => $stagingDir,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerBasicExecution(): array
    {
        return [
            [
                'composerCommand' => [self::INERT_COMMAND],
                'stagingDir' => 'one/two',
            ],
            [
                'composerCommand' => [
                    'update',
                    '--with-all-dependencies',
                ],
                'stagingDir' => 'three/four',
            ],
        ];
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerCommandFailure
     */
    public function testCommandFailure($exception, $message): void
    {
        $this->stager
            ->stage(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['composer-command' => [static::INERT_COMMAND]]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new DirectoryNotFoundException(static::STAGING_DIR, 'Lorem'), 'message' => 'Lorem'],
            ['exception' => new DirectoryNotWritableException(static::STAGING_DIR, 'Ipsum'), 'message' => 'Ipsum'],
            ['exception' => new InvalidArgumentException('Dolor'), 'message' => 'Dolor'],
            ['exception' => new ProcessFailedException('Sit'), 'message' => 'Sit'],
        ];
    }
}
