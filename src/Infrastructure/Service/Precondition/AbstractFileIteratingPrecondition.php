<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Infrastructure\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList;

/**
 * @package Precondition
 *
 * @api
 */
abstract class AbstractFileIteratingPrecondition extends AbstractPrecondition
{
    /**
     * @param string $codebaseName
     *   The name of the codebase in question, i.e., "active" or "staging".
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $codebaseRoot
     *   The codebase root directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $file
     *   The file in question.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     */
    abstract protected function assertIsSupportedFile(
        string $codebaseName,
        PathInterface $codebaseRoot,
        PathInterface $file,
    ): void;

    public function __construct(
        protected readonly FileFinderInterface $fileFinder,
        protected readonly FilesystemInterface $filesystem,
        protected readonly PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($translatableFactory, $translator);
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        try {
            $exclusions ??= new PathList();
            $exclusions->add($stagingDir->resolved());

            if ($this->exitEarly($activeDir, $stagingDir, $exclusions)) {
                return;
            }

            $directories = [
                'active' => $activeDir,
                'staging' => $stagingDir,
            ];

            foreach ($directories as $directoryName => $directoryRootDir) {
                $files = $this->findFiles($directoryRootDir, $exclusions);

                foreach ($files as $file) {
                    $file = $this->pathFactory::create($file);
                    $this->assertIsSupportedFile($directoryName, $directoryRootDir, $file);
                }
            }
        } catch (ExceptionInterface $e) {
            throw new PreconditionException($this, $e->getTranslatableMessage(), 0, $e);
        }
    }

    /** Determines whether to exit the "is fulfilled" test early, before expensive scanning for links. */
    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions,
    ): bool {
        return false;
    }

    /**
     * @return array<string>
     *
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
