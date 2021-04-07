<?php

namespace PhpTuf\ComposerStager\Tests\Console\Command;

use PhpTuf\ComposerStager\Console\Command\CommitCommand;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Tests\Console\CommandTestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Command\CommitCommand
 * @uses \PhpTuf\ComposerStager\Console\Application
 * @uses \PhpTuf\ComposerStager\Console\Command\CommitCommand
 */
class CommitCommandTest extends CommandTestCase
{
    protected function createCommand(): Command
    {
        return new CommitCommand();
    }

    /**
     * @covers ::configure
     */
    public function testBasicConfiguration(): void
    {
        $command = $this->createCommand();

        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('commit', $command->getName(), 'Set correct name.');
        self::assertSame([], $command->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($command->getDescription(), 'Set a description.');
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
}
