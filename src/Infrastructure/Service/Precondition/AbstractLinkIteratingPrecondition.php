<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;

abstract class AbstractLinkIteratingPrecondition extends AbstractPrecondition
{
    protected string $defaultUnfulfilledStatusMessage;

    protected RecursiveFileFinderInterface $fileFinder;

    protected FilesystemInterface $filesystem;

    protected PathFactoryInterface $pathFactory;

    final protected function getUnfulfilledStatusMessage(): string
    {
        return $this->defaultUnfulfilledStatusMessage;
    }

    abstract protected function getDefaultUnfulfilledStatusMessage(): string;

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\IOException */
    abstract protected function isSupportedLink(PathInterface $file, PathInterface $directory): bool;

    public function __construct(
        RecursiveFileFinderInterface $fileFinder,
        FilesystemInterface $filesystem,
        PathFactoryInterface $pathFactory
    ) {
        $this->fileFinder = $fileFinder;
        $this->filesystem = $filesystem;
        $this->pathFactory = $pathFactory;
        $this->defaultUnfulfilledStatusMessage = $this->getDefaultUnfulfilledStatusMessage();
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null
    ): bool {
        try {
            $exclusions ??= new PathList([]);
            $exclusions->add([$stagingDir->resolve()]);

            if ($this->exitEarly($activeDir, $stagingDir, $exclusions)) {
                return true;
            }

            $directories = [
                'active' => $activeDir,
                'staging' => $stagingDir,
            ];

            foreach ($directories as $name => $path) {
                $files = $this->findFiles($path, $exclusions);

                foreach ($files as $file) {
                    $file = $this->pathFactory::create($file);

                    if (!$this->filesystem->isLink($file)) {
                        continue;
                    }

                    if (!$this->isSupportedLink($file, $path)) {
                        $this->defaultUnfulfilledStatusMessage = sprintf(
                            $this->defaultUnfulfilledStatusMessage,
                            $name,
                            $path->resolve(),
                            $file->resolve(),
                        );

                        return false;
                    }
                }
            }
        } catch (InvalidArgumentException|IOException $e) {
            // If something goes wrong, don't throw an exception--just consider the precondition
            // unfulfilled and pass details along to the user via the status message.
            // @todo Find a way to bubble this exception up as the $previous
            //   argument to PreconditionException in ::assertIsFulfilled().
            $this->defaultUnfulfilledStatusMessage = $e->getMessage();

            return false;
        }

        return true;
    }

    /** Determines whether to exit the "is fulfilled" test early, before expensive scanning for links. */
    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions
    ): bool {
        // @todo Remove codeCoverageIgnore.
        return false; // @codeCoverageIgnore
    }

    /**
     * @return array<string>
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     */
    protected function findFiles(PathInterface $path, PathListInterface $exclusions): array
    {
        // Ignore non-existent directories.
        if (!$this->filesystem->exists($path)) {
            return [];
        }

        return $this->fileFinder->find($path, $exclusions);
    }
}
