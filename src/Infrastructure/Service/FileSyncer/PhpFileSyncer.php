<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;

final class PhpFileSyncer implements PhpFileSyncerInterface
{
    public function __construct(
        private readonly RecursiveFileFinderInterface $fileFinder,
        private readonly FilesystemInterface $filesystem,
        private readonly PathFactoryInterface $pathFactory,
    ) {
    }

    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        set_time_limit((int) $timeout);

        $exclusions ??= new PathList([]);

        $this->assertSourceAndDestinationAreDifferent($source, $destination);
        $this->assertSourceExists($source);
        $this->ensureDestinationExists($destination);
        $this->deleteExtraneousFilesFromDestination($destination, $source, $exclusions);
        $this->copySourceFilesToDestination($source, $destination, $exclusions);
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException */
    private function assertSourceAndDestinationAreDifferent(PathInterface $source, PathInterface $destination): void
    {
        $source = $source->resolve();

        if ($source === $destination->resolve()) {
            throw new LogicException(
                sprintf('The source and destination directories cannot be the same at "%s"', $source),
            );
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException */
    private function assertSourceExists(PathInterface $source): void
    {
        if (!$this->filesystem->exists($source)) {
            throw new LogicException(sprintf(
                'The source directory does not exist at "%s"',
                $source->resolve(),
            ));
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\IOException */
    private function ensureDestinationExists(PathInterface $destination): void
    {
        // Create the destination directory if it doesn't already exist.
        $this->filesystem->mkdir($destination);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     */
    private function deleteExtraneousFilesFromDestination(
        PathInterface $destination,
        PathInterface $source,
        PathListInterface $exclusions,
    ): void {
        // There's no reason to look for deletions if the destination is already empty.
        if ($this->destinationIsEmpty($destination)) {
            return;
        }

        $destinationFiles = $this->fileFinder->find($destination, $exclusions);

        $sourceResolved = $source->resolve();
        $destinationResolved = $destination->resolve();

        foreach ($destinationFiles as $destinationFilePathname) {
            $relativePathname = self::getRelativePath($destinationResolved, $destinationFilePathname);
            $sourceFilePathname = $sourceResolved . DIRECTORY_SEPARATOR . $relativePathname;

            $sourceFilePath = $this->pathFactory::create($sourceFilePathname);

            if ($this->filesystem->exists($sourceFilePath)) {
                continue;
            }

            $destinationFilePath = $this->pathFactory::create($destinationFilePathname);

            // If it doesn't exist in the source, delete it from the destination.
            $this->filesystem->remove($destinationFilePath);
        }
    }

    private function destinationIsEmpty(PathInterface $destination): bool
    {
        return scandir($destination->resolve()) === ['.', '..'];
    }

    /**
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     */
    private function copySourceFilesToDestination(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions,
    ): void {
        $sourceFiles = $this->fileFinder->find($source, $exclusions);

        $sourceResolved = $source->resolve();
        $destinationResolved = $destination->resolve();

        foreach ($sourceFiles as $sourceFilePathname) {
            // Once support for Symfony 4 is dropped, see if any of this logic can be
            // eliminated in favor of the new path manipulation utilities in Symfony 5.4:
            // https://symfony.com/doc/5.4/components/filesystem.html#path-manipulation-utilities
            $relativePathname = self::getRelativePath($sourceResolved, $sourceFilePathname);
            $destinationFilePathname = $destinationResolved . DIRECTORY_SEPARATOR . $relativePathname;

            $sourceFilePathname = $this->pathFactory::create($sourceFilePathname);
            $destinationFilePathname = $this->pathFactory::create($destinationFilePathname);

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

        if (strpos($path, $ancestor) === 0) {
            $path = substr($path, strlen($ancestor));
        }

        return $path;
    }
}
