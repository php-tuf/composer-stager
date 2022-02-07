<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Cleaner;

use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class Cleaner implements CleanerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface
     */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function clean(
        PathInterface $stagingDir,
        ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $stagingDirResolved = $stagingDir->resolve();
        if (!$this->directoryExists($stagingDir)) {
            throw new DirectoryNotFoundException($stagingDirResolved, 'The staging directory does not exist at "%s"');
        }

        $this->filesystem->remove($stagingDirResolved, $callback, $timeout);
    }

    public function directoryExists(PathInterface $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir->resolve());
    }
}
