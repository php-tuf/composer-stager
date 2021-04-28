<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Domain\Stager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StageCommand extends Command
{
    protected static $defaultName = 'stage';

    /**
     * @var \PhpTuf\ComposerStager\Domain\Stager
     */
    private $stager;

    public function __construct(Stager $stager)
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
            ->setDescription('Stages a Composer command')
            ->addArgument(
                'composer-command',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The raw Composer command to stage. This MUST be preceded by a double-hyphen (" -- ") to prevent confusion of command options. See "Usage"'
            )
            ->addUsage('[options] -- <composer-command>...')
            ->addUsage('-- update --with-all-dependencies')
            ->addUsage('-- require lorem/ipsum:"^1 || ^2"')
            ->addUsage('-- --help')
            ->addUsage('--ansi -- help --ansi')
            ->setHelp('If you are getting unexpected behavior from command options, be sure you are preceding the "composer-command" argument with a double-hyphen (" -- "). See "Usage"')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int The exit code.
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var string[] $composerCommand */
            $composerCommand = $input->getArgument('composer-command');
            /** @var string $stagingDir */
            $stagingDir = $input->getOption('staging-dir');

            $return = $this->stager->stage(
                $composerCommand,
                $stagingDir
            );

            $output->write($return);
            return ExitCode::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return ExitCode::FAILURE;
        }
    }
}
