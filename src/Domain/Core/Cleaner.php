<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Process\Service\ProcessRunnerInterface;

/**
 * @package Core
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
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
