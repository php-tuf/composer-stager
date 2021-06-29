<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStager\Domain\CommitterInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class CommitCommand extends AbstractCommand
{
    private const NAME = 'commit';

    /**
     * @var \PhpTuf\ComposerStager\Domain\CommitterInterface
     */
    private $committer;

    public function __construct(CommitterInterface $committer)
    {
        parent::__construct(self::NAME);
        $this->committer = $committer;
    }

    protected function configure(): void
    {
        $this->setDescription('Makes the staged changes live by syncing the active directory with the staging directory');
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $activeDir */
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        /** @var string $stagingDir */
        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);

        try {
            $this->committer->commit(
                $stagingDir,
                $activeDir,
                new ProcessOutputCallback($input, $output)
            );

            return self::SUCCESS;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return self::FAILURE;
        }
    }
}
