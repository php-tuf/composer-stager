<?php

namespace PhpTuf\ComposerStager\Tests\Console\Command;

use PhpTuf\ComposerStager\Console\Command\BeginCommand;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Domain\BeginnerInterface;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Tests\Console\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\BeginCommand
 * @covers ::__construct
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\BeginCommand
 * @uses \PhpTuf\ComposerStager\Console\Output\Callback
 *
 * @property \PhpTuf\ComposerStager\Domain\BeginnerInterface|\Prophecy\Prophecy\ObjectProphecy beginner
 */
class BeginCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->beginner = $this->prophesize(BeginnerInterface::class);
        $this->beginner
            ->begin(Argument::cetera());
        parent::setUp();
    }

    protected function createSut(): Command
    {
        $beginner = $this->beginner->reveal();
        return new BeginCommand($beginner);
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

        self::assertSame('begin', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::execute
     */
    public function testBasicExecution(): void
    {
        $this->executeCommand();

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(ExitCode::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerCommandFailure
     */
    public function testCommandFailure($exception, $message): void
    {
        $this->beginner
            ->begin(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand([]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(ExitCode::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new DirectoryAlreadyExistsException('', 'Lorem'), 'message' => 'Lorem'],
            ['exception' => new DirectoryNotFoundException('', 'Ipsum'), 'message' => 'Ipsum'],
            ['exception' => new ProcessFailedException('Dolor'), 'message' => 'Dolor'],
        ];
    }
}
