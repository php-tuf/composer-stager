<?php

namespace PhpTuf\ComposerStager\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class CommitCommand extends AbstractCommand
{
    private const NAME = 'commit';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Makes the staged changes live by syncing the active directory with the staging directory');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}
