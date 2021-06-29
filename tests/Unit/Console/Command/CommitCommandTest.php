<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Command\AbstractCommand;
use PhpTuf\ComposerStager\Console\Command\CommitCommand;
use PhpTuf\ComposerStager\Domain\CommitterInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Tests\Unit\Console\CommandTestCase;
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
class CommitCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->committer = $this->prophesize(CommitterInterface::class);
        $this->committer
            ->commit(Argument::cetera());
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
     * @covers ::execute
     *
     * @dataProvider providerBasicExecution
     */
    public function testBasicExecution($activeDir, $stagingDir): void
    {
        $this->committer
            ->commit($stagingDir, $activeDir, Argument::any())
            ->shouldBeCalledOnce();

        $this->executeCommand([
            '--' . Application::ACTIVE_DIR_OPTION => $activeDir,
            '--' . Application::STAGING_DIR_OPTION => $stagingDir,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerBasicExecution(): array
    {
        return [
            [
                'activeDir' => '/lorem/ipsum',
                'stagingDir' => '/dolor/sit',
            ],
            [
                'activeDir' => '/amet/consectetur',
                'stagingDir' => '/adispiscin/elit',
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

        $this->executeCommand();

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
