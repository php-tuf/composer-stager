<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends Command
{
    protected static $defaultName = 'clean';

    protected function configure(): void
    {
        $this
            ->setDescription('Removes the staging directory')
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return ExitCode::SUCCESS;
    }
}
