<?php

namespace PhpTuf\ComposerStager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BeginCommand extends Command
{
    protected static $defaultName = 'begin';

    protected function configure(): void
    {
        $this
            ->setDescription('Begins staging Composer')
            ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return StatusCode::OK;
    }
}
