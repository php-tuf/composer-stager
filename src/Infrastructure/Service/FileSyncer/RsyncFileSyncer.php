<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList;

/**
 * @package FileSyncer
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class RsyncFileSyncer implements RsyncFileSyncerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly RsyncRunnerInterface $rsync,
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

        set_time_limit((int) $timeout);

        $exclusions ??= new PathList();
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $exclusions = $exclusions->getAll();

        if ($sourceResolved === $destinationResolved) {
            throw new LogicException($this->t(
                'The source and destination directories cannot be the same at %path',
                $this->p(['%path' => $sourceResolved]),
            ));
        }

        if (!$this->filesystem->exists($source)) {
            throw new LogicException($this->t(
                'The source directory does not exist at %path',
                $this->p(['%path' => $sourceResolved]),
            ));
        }

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
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $sourceResolved . DIRECTORY_SEPARATOR;

        $command[] = $destinationResolved;

        // Ensure the destination directory's existence. (This has no effect
        // if it already exists.)
        $this->filesystem->mkdir($destination);

        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new IOException($e->getTranslatableMessage(), 0, $e);
        }
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
