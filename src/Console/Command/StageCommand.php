<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Domain\StagerInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StageCommand extends Command
{
    protected static $defaultName = 'stage';

    /**
     * @var \PhpTuf\ComposerStager\Domain\StagerInterface
     */
    private $stager;

    public function __construct(StagerInterface $stager)
    {
        parent::__construct(static::$defaultName);
        $this->stager = $stager;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Executes a Composer command in the staging directory')
            ->addArgument(
                'composer-command',
                // The argument uses array mode so that it's automatically
                // parsed and escaped by the Console component. This approach,
                // though safer and easier, requires the command string to be
                // preceded by a double-hyphen (" -- ") or ALL options will
                // always be applied to the "stage" and never staged, regardless
                // of placement.
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The Composer command to stage, without "composer". This MUST be preceded by a double-hyphen (" -- ") to prevent confusion of command options. See "Usage"'
            )
            ->addUsage('[options] -- <composer-command>...')
            ->addUsage('-- update --with-all-dependencies')
            ->addUsage('-- require lorem/ipsum:"^1 || ^2"')
            ->addUsage('-- --help')
            ->setHelp('If you are getting unexpected behavior from command options, be sure you are preceding the "composer-command" argument with a double-hyphen (" -- "). See "Usage"')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int The exit code.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $composerCommand */
        $composerCommand = $input->getArgument('composer-command');
        /** @var string $stagingDir */
        $stagingDir = $input->getOption('staging-dir');

        // Write process output as it comes.
        /** @see \Symfony\Component\Process\Process::readPipes */
        $callback = static function ($type, string $buffer) use ($output): void {
            $output->write($buffer); // @codeCoverageIgnore
        };

        try {
            $this->stager->stage(
                $composerCommand,
                $stagingDir,
                $callback
            );

            return ExitCode::SUCCESS;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return ExitCode::FAILURE;
        }
    }
}
