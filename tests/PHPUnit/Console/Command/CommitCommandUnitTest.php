<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Command\AbstractCommand;
use PhpTuf\ComposerStager\Console\Command\CommitCommand;
use PhpTuf\ComposerStager\Domain\CommitterInterface;
use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Tests\PHPUnit\Console\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\CommitCommand
 * @covers \PhpTuf\ComposerStager\Console\Command\CommitCommand::__construct
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\CommitCommand
 * @uses \PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback
 *
 * @property \PhpTuf\ComposerStager\Domain\CommitterInterface|\Prophecy\Prophecy\ObjectProphecy committer
 */
class CommitCommandUnitTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->committer = $this->prophesize(CommitterInterface::class);
        $this->committer
            ->commit(Argument::cetera());
        $this->committer
            ->directoryExists(Argument::cetera())
            ->willReturn(true);
        parent::setUp();
    }

    protected function createSut(): Command
    {
        $committer = $this->committer->reveal();
        return new CommitCommand($committer);
    }

    /**
     * @covers ::configure
     */
    public function testBasicConfiguration(): void
    {
        $sut = $this->createSut();

        $definition = $sut->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('commit', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     *
     * @dataProvider providerBasicExecution
     */
    public function testBasicExecution($activeDir, $stagingDir): void
    {
        $this->committer
            ->commit($stagingDir, $activeDir, null, Argument::type(ProcessOutputCallbackInterface::class))
            ->shouldBeCalledOnce();

        $this->executeCommand([
            '--' . Application::ACTIVE_DIR_OPTION => $activeDir,
            '--' . Application::STAGING_DIR_OPTION => $stagingDir,
            '--no-interaction' => true,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerBasicExecution(): array
    {
        return [
            [
                'activeDir' => '/one/two',
                'stagingDir' => '/three/four',
            ],
            [
                'activeDir' => '/five/six',
                'stagingDir' => '/seven/eight',
            ],
        ];
    }

    /**
     * @covers ::execute
     */
    public function testStagingDirectoryNotFound(): void
    {
        $this->committer
            ->directoryExists(Argument::cetera())
            ->willReturn(false);
        $this->committer
            ->commit(Argument::cetera())
            ->shouldNotBeCalled();

        $this->executeCommand(['--no-interaction' => true]);

        self::assertStringContainsString('staging directory does not exist', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     *
     * @dataProvider providerConfirmationPrompt
     */
    public function testConfirmationPrompt($input, $calls, $exit): void
    {
        $this->committer
            ->commit(Argument::cetera())
            ->shouldBeCalledTimes($calls);

        $this->executeCommand([], [$input]);

        self::assertStringContainsString('Continue?', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame($exit, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerConfirmationPrompt(): array
    {
        return [
            [
                'input' => 'yes',
                'calls' => 1,
                'exit' => AbstractCommand::SUCCESS,
            ],
            [
                'input' => 'no',
                'calls' => 0,
                'exit' => AbstractCommand::FAILURE,
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
        $this->committer
            ->commit(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['--no-interaction' => true]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new DirectoryNotFoundException('', 'Ipsum'), 'message' => 'Ipsum'],
            ['exception' => new ProcessFailedException('Dolor'), 'message' => 'Dolor'],
        ];
    }
}
