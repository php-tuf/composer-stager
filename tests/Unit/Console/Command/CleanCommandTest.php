<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Command\AbstractCommand;
use PhpTuf\ComposerStager\Console\Command\CleanCommand;
use PhpTuf\ComposerStager\Domain\CleanerInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Tests\Unit\Console\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\CleanCommand
 * @covers \PhpTuf\ComposerStager\Console\Command\CleanCommand::__construct
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\CleanCommand::configure
 * @uses \PhpTuf\ComposerStager\Console\Command\CleanCommand::confirm
 *
 * @property \PhpTuf\ComposerStager\Domain\Cleaner|\Prophecy\Prophecy\ObjectProphecy cleaner
 */
class CleanCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->cleaner = $this->prophesize(CleanerInterface::class);
        $this->cleaner
            ->directoryExists(Argument::cetera())
            ->willReturn(true);
        parent::setUp();
    }

    protected function createSut(): Command
    {
        $cleaner = $this->cleaner->reveal();
        return new CleanCommand($cleaner);
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

        self::assertSame('clean', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     */
    public function testBasicExecution(): void
    {
        $this->cleaner
            ->clean(static::STAGING_DIR)
            ->shouldBeCalledOnce();

        $this->executeCommand([
            sprintf('--%s', Application::STAGING_DIR_OPTION) => static::STAGING_DIR,
            '--no-interaction' => true,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    /**
     * @covers ::execute
     */
    public function testStagingDirectoryNotFound(): void
    {
        $this->cleaner
            ->directoryExists(Argument::cetera())
            ->willReturn(false);
        $this->cleaner
            ->clean(Argument::cetera())
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
        $this->cleaner
            ->clean(Argument::cetera())
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
        $this->cleaner
            ->clean(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['--no-interaction' => true]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new IOException('Lorem'), 'message' => 'Lorem'],
            ['exception' => new DirectoryNotWritableException(static::STAGING_DIR, 'Ipsum'), 'message' => 'Ipsum'],
        ];
    }
}
