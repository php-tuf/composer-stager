<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Beginner;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class Beginner implements BeginnerInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface */
    private $fileSyncer;

    /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface */
    private $preconditions;

    public function __construct(FileSyncerInterface $fileSyncer, BeginnerPreconditionsInterface $preconditions)
    {
        $this->fileSyncer = $fileSyncer;
        $this->preconditions = $preconditions;
    }

    public function begin(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir);

        try {
            $this->fileSyncer->sync($activeDir, $stagingDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
