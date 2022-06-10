<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Stager;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class Stager implements StagerInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ComposerRunnerInterface */
    private $composerRunner;

    /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface */
    private $preconditions;

    public function __construct(ComposerRunnerInterface $composerRunner, StagerPreconditionsInterface $preconditions)
    {
        $this->composerRunner = $composerRunner;
        $this->preconditions = $preconditions;
    }

    public function stage(
        array $composerCommand,
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir);

        $this->validateCommand($composerCommand);

        $this->runCommand($stagingDir, $composerCommand, $callback, $timeout);
    }

    /**
     * @param array<string> $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    private function validateCommand(array $composerCommand): void
    {
        if ($composerCommand === []) {
            throw new InvalidArgumentException('The Composer command cannot be empty');
        }

        if (reset($composerCommand) === 'composer') {
            throw new InvalidArgumentException('The Composer command cannot begin with "composer"--it is implied');
        }

        if (array_key_exists('--working-dir', $composerCommand) || array_key_exists('-d', $composerCommand)) {
            throw new InvalidArgumentException(
                'Cannot stage a Composer command containing the "--working-dir" (or "-d") option',
            );
        }
    }

    /**
     * @param array<string> $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     */
    private function runCommand(
        PathInterface $stagingDir,
        array $composerCommand,
        ?ProcessOutputCallbackInterface $callback,
        ?int $timeout
    ): void {
        $command = array_merge(
            ['--working-dir=' . $stagingDir->resolve()],
            $composerCommand,
        );

        try {
            $this->composerRunner->run($command, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
