<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Helper\PathHelper;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class RsyncFileSyncer implements RsyncFileSyncerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly FilesystemInterface $filesystem,
        private readonly RsyncProcessRunnerInterface $rsync,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    /**
     * The unusual requirement to support syncing a directory with its own
     * descendant requires a unique approach, which has been documented here:
     *
     * @see https://serverfault.com/q/1094803/956603
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?OutputCallbackInterface $callback = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->environment->setTimeLimit($timeout);

        $sourceAbsolute = $source->absolute();
        $destinationAbsolute = $destination->absolute();

        $this->assertDirectoriesAreNotTheSame($source, $destination);
        $this->assertSourceExists($source);
        $this->runCommand($exclusions, $sourceAbsolute, $destinationAbsolute, $destination, $callback);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertDirectoriesAreNotTheSame(PathInterface $source, PathInterface $destination): void
    {
        $sourceAbsolute = $source->absolute();
        $destinationAbsolute = $destination->absolute();

        if ($sourceAbsolute === $destinationAbsolute) {
            throw new LogicException($this->t(
                'The source and destination directories cannot be the same at %path',
                $this->p(['%path' => $sourceAbsolute]),
                $this->d()->exceptions(),
            ));
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
    private function runCommand(
        ?PathListInterface $exclusions,
        string $sourceAbsolute,
        string $destinationAbsolute,
        PathInterface $destination,
        ?OutputCallbackInterface $callback,
    ): void {
        $sourceAbsolute = PathHelper::canonicalize($sourceAbsolute);
        $destinationAbsolute = PathHelper::canonicalize($destinationAbsolute);

        $this->ensureDestinationDirectoryExists($destination);
        $command = $this->buildCommand($exclusions, $sourceAbsolute, $destinationAbsolute);

        try {
            $this->rsync->run($command, $callback);
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

        // There's no reason to process duplicates.
        $exclusions = array_unique($exclusions);

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=/' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $sourceAbsolute . DIRECTORY_SEPARATOR;

        $command[] = $destinationAbsolute;

        return $command;
    }

    private function isDescendant(string $descendant, string $ancestor): bool
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        return str_starts_with($descendant, $ancestor);
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        return substr($path, strlen($ancestor) + 1);
    }
}
