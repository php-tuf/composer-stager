<?php

namespace PhpTuf\ComposerStager\Console\Command;

use PhpTuf\ComposerStager\Console\Misc\ExitCode;
use PhpTuf\ComposerStager\Domain\CleanerInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @internal
 */
final class CleanCommand extends Command
{
    private const NAME = 'clean';

    /**
     * @var \PhpTuf\ComposerStager\Domain\CleanerInterface
     */
    private $cleaner;

    public function __construct(CleanerInterface $cleaner)
    {
        parent::__construct(self::NAME);
        $this->cleaner = $cleaner;
    }

    protected function configure(): void
    {
        $this->setDescription('Removes the staging directory');
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $stagingDir */
        $stagingDir = $input->getOption('staging-dir');

        if (!$this->cleaner->directoryExists($stagingDir)) {
            $output->writeln(sprintf('<error>The staging directory does not exist at "%s"</error>', $stagingDir));
            return ExitCode::FAILURE;
        }

        if (!$this->confirm($input, $output)) {
            return ExitCode::FAILURE;
        }

        try {
            $this->cleaner->clean($stagingDir);

            return ExitCode::SUCCESS;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return ExitCode::FAILURE;
        }
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function confirm(InputInterface $input, OutputInterface $output): bool
    {
        /** @var bool $noInteraction */
        $noInteraction = $input->getOption('no-interaction');
        if ($noInteraction) {
            return true;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('You are about to permanently remove the staging directory. This action cannot be undone. Continue? ');
        return $helper->ask($input, $output, $question);
    }
}
