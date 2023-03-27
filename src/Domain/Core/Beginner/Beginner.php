<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Beginner;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class Beginner implements BeginnerInterface
{
    public function __construct(
        private readonly FileSyncerInterface $fileSyncer,
        private readonly BeginnerPreconditionsInterface $preconditions,
    ) {
    }

    public function begin(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir, $exclusions);

        try {
            $this->fileSyncer->sync($activeDir, $stagingDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
