<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PhpFileSyncer extends AbstractFileSyncer implements PhpFileSyncerInterface
{
    public function __construct(
        EnvironmentInterface $environment,
        private readonly FileFinderInterface $fileFinder,
        FilesystemInterface $filesystem,
        private readonly PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($environment, $filesystem, $translatableFactory);
    }

    protected function doSync(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions,
        ?OutputCallbackInterface $callback,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->ensureDestinationExists($destination);
        $this->deleteExtraneousFilesFromDestination($destination, $source, $exclusions);
        $this->copySourceFilesToDestination($source, $destination, $exclusions);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\IOException */
    private function ensureDestinationExists(PathInterface $destination): void
    {
        // Create the destination directory if it doesn't already exist.
        $this->filesystem->mkdir($destination);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\IOException */
    private function deleteExtraneousFilesFromDestination(
        PathInterface $destination,
        PathInterface $source,
        PathListInterface $exclusions,
    ): void {
        // There's no reason to look for deletions if the destination is already empty.
        if ($this->filesystem->isDirEmpty($destination)) {
            return;
        }

        $destinationFiles = $this->fileFinder->find($destination, $exclusions);

        foreach ($destinationFiles as $destinationFileAbsolute) {
            $fileRelative = self::getRelativePath($destination->absolute(), $destinationFileAbsolute);
            $sourceFilePath = $this->pathFactory->create($fileRelative, $source);

            if ($this->filesystem->fileExists($sourceFilePath)) {
                // @infection-ignore-all Continue_Survived is clearly a
                //   false positive here. It's absolutely caught by tests.
                continue;
            }

            $destinationFilePath = $this->pathFactory->create($destinationFileAbsolute);

            // If it doesn't exist in the source, delete it from the destination.
            $this->filesystem->remove($destinationFilePath);
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     */
    private function copySourceFilesToDestination(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions,
    ): void {
        $sourceFiles = $this->fileFinder->find($source, $exclusions);

        foreach ($sourceFiles as $sourceFileAbsolute) {
            $fileRelative = self::getRelativePath($source->absolute(), $sourceFileAbsolute);
            $destinationFileAbsolute = $destination->absolute() . DIRECTORY_SEPARATOR . $fileRelative;

            $sourceFilePath = $this->pathFactory->create($sourceFileAbsolute);
            $destinationFilePath = $this->pathFactory->create($destinationFileAbsolute);

            // Copy the file--even if it already exists and is identical in the
            // destination. Obviously, this has performance implications, but
            // for lots of small files (the primary use case), the cost of
            // checking differences first would surely outweigh any savings.
            $this->filesystem->copy($sourceFilePath, $destinationFilePath);
        }
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        if (str_starts_with($path, $ancestor)) {
            return substr($path, strlen($ancestor));
        }

        return $path;
    }
}
