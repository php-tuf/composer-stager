<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PhpFileSyncer implements PhpFileSyncerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly FileFinderInterface $fileFinder,
        private readonly FilesystemInterface $filesystem,
        private readonly PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?OutputCallbackInterface $callback = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->environment->setTimeLimit($timeout);

        $exclusions ??= new PathList();

        $this->assertSourceAndDestinationAreDifferent($source, $destination);
        $this->assertSourceExists($source);
        $this->ensureDestinationExists($destination);
        $this->deleteExtraneousFilesFromDestination($destination, $source, $exclusions);
        $this->copySourceFilesToDestination($source, $destination, $exclusions);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertSourceAndDestinationAreDifferent(PathInterface $source, PathInterface $destination): void
    {
        if ($source->absolute() === $destination->absolute()) {
            throw new LogicException(
                $this->t(
                    'The source and destination directories cannot be the same at %path',
                    $this->p(['%path' => $source->absolute()]),
                    $this->d()->exceptions(),
                ),
            );
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertSourceExists(PathInterface $source): void
    {
        if (!$this->filesystem->exists($source)) {
            throw new LogicException($this->t(
                'The source directory does not exist at %path',
                $this->p(['%path' => $source->absolute()]),
                $this->d()->exceptions(),
            ));
        }
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
        $destinationAbsolute = $destination->absolute();

        foreach ($destinationFiles as $destinationFilePathname) {
            $relativePathname = self::getRelativePath($destinationAbsolute, $destinationFilePathname);
            $sourceFilePath = $this->pathFactory->create($relativePathname, $source);

            if ($this->filesystem->exists($sourceFilePath)) {
                continue;
            }

            $destinationFilePath = $this->pathFactory->create($destinationFilePathname);

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

        $sourceAbsolute = $source->absolute();
        $destinationAbsolute = $destination->absolute();

        foreach ($sourceFiles as $sourceFilePathname) {
            $relativePathname = self::getRelativePath($sourceAbsolute, $sourceFilePathname);
            $destinationFilePathname = $destinationAbsolute . DIRECTORY_SEPARATOR . $relativePathname;

            $sourceFilePathname = $this->pathFactory->create($sourceFilePathname);
            $destinationFilePathname = $this->pathFactory->create($destinationFilePathname);

            // Copy the file--even if it already exists and is identical in the
            // destination. Obviously, this has performance implications, but
            // for lots of small files (the primary use case), the cost of
            // checking differences first would surely outweigh any savings.
            $this->filesystem->copy($sourceFilePathname, $destinationFilePathname);
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
