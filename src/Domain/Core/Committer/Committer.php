<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Committer;

use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class Committer implements CommitterInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface */
    private $fileSyncer;

    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FileSyncerInterface $fileSyncer, FilesystemInterface $filesystem)
    {
        $this->fileSyncer = $fileSyncer;
        $this->filesystem = $filesystem;
    }

    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        $stagingDirResolved = $stagingDir->resolve();

        if (!$this->filesystem->exists($stagingDirResolved)) {
            throw new DirectoryNotFoundException($stagingDirResolved, 'The staging directory does not exist at "%s"');
        }

        $activeDirResolved = $activeDir->resolve();

        if (!$this->filesystem->exists($activeDirResolved)) {
            throw new DirectoryNotFoundException($activeDirResolved, 'The active directory does not exist at "%s"');
        }

        if (!$this->filesystem->isWritable($activeDirResolved)) {
            throw new DirectoryNotWritableException($activeDirResolved, 'The active directory is not writable at "%s"');
        }

        try {
            $this->fileSyncer->sync($stagingDir, $activeDir, $exclusions, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function directoryExists(string $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir);
    }
}
