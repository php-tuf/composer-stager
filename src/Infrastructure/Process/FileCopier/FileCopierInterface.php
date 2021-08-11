<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Copies files from one location to another.
 */
interface FileCopierInterface
{
    /**
     * Copies files.
     *
     * @param string $from
     *   The source ("from") directory as an absolute path or relative to the
     *   working directory (CWD), e.g., "/var/www/example" or "example".
     * @param string $to
     *   The destination ("to") directory as an absolute path or relative to the
     *   working directory (CWD), e.g., "/var/www/example" or "example".
     * @param string[] $exclusions
     *   Paths to exclude, relative to the "from" path.
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the source ("from") directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function copy(
        string $from,
        string $to,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
