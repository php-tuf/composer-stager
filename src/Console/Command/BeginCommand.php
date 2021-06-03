<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BeginCommand extends Command
{
    private const NAME = 'begin';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return ExitCode::SUCCESS;
    }
}
