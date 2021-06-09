<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Console\Output\ProcessCallback;
use PhpTuf\ComposerStager\Domain\BeginnerInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class BeginCommand extends Command
{
    private const NAME = 'begin';

    /**
     * @var \PhpTuf\ComposerStager\Domain\BeginnerInterface
     */
    private $beginner;

    public function __construct(BeginnerInterface $beginner)
    {
        parent::__construct(self::NAME);
        $this->beginner = $beginner;
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
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

        if (!$this->beginner->activeDirectoryExists($activeDir)) {
            $output->writeln(sprintf('<error>The active directory does not exist at "%s"</error>', $activeDir));
            return ExitCode::FAILURE;
        }

        if ($this->beginner->stagingDirectoryExists($stagingDir)) {
            $output->writeln(sprintf('<error>The staging directory already exists at "%s"</error>', $stagingDir));
            return ExitCode::FAILURE;
        }

        try {
            $this->beginner->begin(
                $activeDir,
                $stagingDir,
                new ProcessCallback($input, $output)
            );

            return ExitCode::SUCCESS;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return ExitCode::FAILURE;
        }
    }
}
