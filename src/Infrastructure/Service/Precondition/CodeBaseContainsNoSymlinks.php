<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;

final class CodeBaseContainsNoSymlinks extends AbstractPrecondition implements CodebaseContainsNoSymlinksInterface
{
    /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface */
    private $fileFinder;

    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    /** @var string */
    private $unfulfilledStatusMessage = 'The codebase contains symlinks.';

    public function __construct(RecursiveFileFinderInterface $fileFinder, FilesystemInterface $filesystem)
    {
        $this->fileFinder = $fileFinder;
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Codebase contains no symlinks'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain symlinks.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        try {
            $files = $this->findFiles($activeDir, $stagingDir);
        } catch (InvalidArgumentException|IOException $e) {
            // If something goes wrong searching for symlinks, don't throw an
            // exception--just consider the precondition unfulfilled and pass
            // details along to the user via the status message.
            $this->unfulfilledStatusMessage = $e->getMessage();

            return false;
        }

        foreach ($files as $file) {
            if (is_link($file)) {
                return false;
            }
        }

        return true;
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The codebase contains no symlinks.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        // This message is defined dynamically so it can be overridden to pass
        // along an exception message from a dependency. See ::isFulfilled.
        return $this->unfulfilledStatusMessage;
    }

    /**
     * @return array<string>
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     */
    private function findFiles(PathInterface $activeDir, PathInterface $stagingDir): array
    {
        $activeDirFiles = $this->fileFinder->find($activeDir);

        if (!$this->filesystem->exists($stagingDir)) {
            return $activeDirFiles;
        }

        $stagingDirFiles = $this->fileFinder->find($stagingDir);

        return array_merge($activeDirFiles, $stagingDirFiles);
    }
}
