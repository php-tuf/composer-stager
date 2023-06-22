<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\Domain;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Process\Service\RsyncProcessRunnerInterface;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class RsyncFileSyncer implements RsyncFileSyncerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
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
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void {
        $sourceResolved = $source->resolved();
        $destinationResolved = $destination->resolved();

        $this->assertDirectoriesAreNotTheSame($source, $destination);
        $this->assertSourceExists($source);
        set_time_limit((int) $timeout);
        $this->runCommand($exclusions, $sourceResolved, $destinationResolved, $destination, $callback);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertDirectoriesAreNotTheSame(PathInterface $source, PathInterface $destination): void
    {
        $sourceResolved = $source->resolved();
        $destinationResolved = $destination->resolved();

        if ($sourceResolved === $destinationResolved) {
            throw new LogicException($this->t(
                'The source and destination directories cannot be the same at %path',
                $this->p(['%path' => $sourceResolved]),
                Domain::EXCEPTIONS,
            ));
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertSourceExists(PathInterface $source): void
    {
        if (!$this->filesystem->exists($source)) {
            throw new LogicException($this->t(
                'The source directory does not exist at %path',
                $this->p(['%path' => $source->resolved()]),
                Domain::EXCEPTIONS,
            ));
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\IOException */
    private function runCommand(
        ?PathListInterface $exclusions,
        string $sourceResolved,
        string $destinationResolved,
        PathInterface $destination,
        ?ProcessOutputCallbackInterface $callback,
    ): void {
        $this->ensureDestinationDirectoryExists($destination);
        $command = $this->buildCommand($exclusions, $sourceResolved, $destinationResolved);

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
        string $sourceResolved,
        string $destinationResolved,
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
        if ($this->isDescendant($sourceResolved, $destinationResolved)) {
            $exclusions[] = self::getRelativePath($destinationResolved, $sourceResolved);
        }

        // There's no reason to process duplicates.
        $exclusions = array_unique($exclusions);

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=/' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $sourceResolved . DIRECTORY_SEPARATOR;

        $command[] = $destinationResolved;

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
