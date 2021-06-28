<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

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
     *
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    public function copy(
        string $from,
        string $to,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null
    ): void;
}
