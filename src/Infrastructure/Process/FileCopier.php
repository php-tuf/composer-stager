<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunner;

/**
 * @internal
 */
class FileCopier
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunner
     */
    private $rsync;

    public function __construct(RsyncRunner $rsync)
    {
        $this->rsync = $rsync;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string[] $exclusions Paths to exclude, relative to the "from" path.
     * @param callable|null $callback An optional PHP callback to run whenever
     *   there is some output available on STDOUT or STDERR.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    public function copy(string $from, string $to, array $exclusions = [], ?callable $callback = null): void
    {
        $command = [
            '--recursive',
            // The "--links" option is added to "copy symlinks as symlinks",
            // which is important particularly for files in vendor/bin.
            '--links',
            '--verbose',
        ];

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the "from" directory so the CONTENTS of
        // the active directory are copied, not the directory itself.
        $command[] = rtrim($from, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $command[] = $to;

        try {
            $this->rsync->run($command, $callback);
        } catch (LogicException $e) {
            throw new ProcessFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
