<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelperInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class RsyncFileSyncer extends AbstractFileSyncer implements RsyncFileSyncerInterface
{
    public function __construct(
        EnvironmentInterface $environment,
        FilesystemInterface $filesystem,
        private readonly PathHelperInterface $pathHelper,
        private readonly RsyncProcessRunnerInterface $rsync,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($environment, $filesystem, $translatableFactory);
    }

    /**
     * The unusual requirement to support syncing a directory with its own
     * descendant requires a unique approach, which has been documented here:
     *
     * @see https://serverfault.com/q/1094803/956603
     */
    protected function doSync(
        PathInterface $source,
        PathInterface $destination,
        PathListInterface $exclusions,
        ?OutputCallbackInterface $callback,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $sourceAbsolute = $source->absolute();
        $destinationAbsolute = $destination->absolute();

        $this->runCommand($exclusions, $sourceAbsolute, $destinationAbsolute, $destination, $callback);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\IOException */
    private function runCommand(
        ?PathListInterface $exclusions,
        string $sourceAbsolute,
        string $destinationAbsolute,
        PathInterface $destination,
        ?OutputCallbackInterface $callback,
    ): void {
        $sourceAbsolute = $this->pathHelper->canonicalize($sourceAbsolute);
        $destinationAbsolute = $this->pathHelper->canonicalize($destinationAbsolute);

        $this->ensureDestinationDirectoryExists($destination);
        $command = $this->buildCommand($exclusions, $sourceAbsolute, $destinationAbsolute);

        try {
            $this->rsync->run($command, null, [], $callback);
        } catch (ExceptionInterface $e) {
            throw new IOException($e->getTranslatableMessage(), 0, $e);
        }
    }

    /**
     * Ensures that the destination directory exists. This has no effect if it already does.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     */
    private function ensureDestinationDirectoryExists(PathInterface $destination): void
    {
        $this->filesystem->mkdir($destination);
    }

    /** @return array<string> */
    private function buildCommand(
        ?PathListInterface $exclusions,
        string $sourceAbsolute,
        string $destinationAbsolute,
    ): array {
        $exclusions ??= new PathList();
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $exclusions = $exclusions->getAll();

        $command = [
            // Archive mode--the same as -rlptgoD (no -H), or --recursive,
            // --links, --perms, --times, --group, --owner, --devices, --specials.
            '--archive',
            // Delete extraneous files from destination directories. Note: Using
            // --delete-after rather than alternatives prevents "file has
            // vanished" errors when syncing a directory with its own ancestor.
            '--delete-after',
            // Increase verbosity.
            '--verbose',
        ];

        // Prevent infinite recursion if the source is inside the destination.
        if ($this->isDescendant($sourceAbsolute, $destinationAbsolute)) {
            $exclusions[] = self::getRelativePath($destinationAbsolute, $sourceAbsolute);
        }

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=/' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $sourceAbsolute . DIRECTORY_SEPARATOR;

        $command[] = $destinationAbsolute;

        return $command;
    }
}
