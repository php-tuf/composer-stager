<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Core;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;

/**
 * @package Core
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class Committer implements CommitterInterface
{
    public function __construct(
        private readonly FileSyncerInterface $fileSyncer,
        private readonly CommitterPreconditionsInterface $preconditions,
    ) {
    }

    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir, $exclusions);

        try {
            $this->fileSyncer->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getTranslatableMessage(), 0, $e);
        }
    }
}
