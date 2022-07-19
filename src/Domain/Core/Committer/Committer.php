<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Committer;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class Committer implements CommitterInterface
{
    private FileSyncerInterface $fileSyncer;

    private PreconditionInterface $preconditions;

    public function __construct(FileSyncerInterface $fileSyncer, CommitterPreconditionsInterface $preconditions)
    {
        $this->fileSyncer = $fileSyncer;
        $this->preconditions = $preconditions;
    }

    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir);

        try {
            $this->fileSyncer->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
