<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Core;

use PhpTuf\ComposerStager\API\Core\CleanerInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;

/**
 * @package Core
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class Cleaner implements CleanerInterface
{
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly CleanerPreconditionsInterface $preconditions,
    ) {
    }

    public function clean(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->preconditions->assertIsFulfilled($activeDir, $stagingDir);

        try {
            $this->filesystem->remove($stagingDir, $callback, $timeout);
        } catch (IOException $e) {
            throw new RuntimeException($e->getTranslatableMessage(), 0, $e);
        }
    }
}
