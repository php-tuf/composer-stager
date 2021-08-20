<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Util\DirectoryUtil;

/**
 * @internal
 */
final class RsyncFileCopier implements RsyncFileCopierInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface
     */
    private $rsync;

    public function __construct(FilesystemInterface $filesystem, RsyncRunnerInterface $rsync)
    {
        $this->filesystem = $filesystem;
        $this->rsync = $rsync;
    }

    public function copy(
        string $from,
        string $to,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        if (!$this->filesystem->exists($from)) {
            throw new DirectoryNotFoundException($from, 'The "copy from" directory does not exist at "%s"');
        }

        $command = [
            // Archive mode; same as -rlptgoD (no -H).
            '--archive',
            // Recurse into directories.
            '--recursive',
            // Increase verbosity.
            '--verbose',
        ];

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the "from" directory so the CONTENTS of
        // the active directory are copied, not the directory itself.
        $command[] = DirectoryUtil::ensureTrailingSlash($from);
        $command[] = $to;

        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
