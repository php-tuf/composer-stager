<?php

namespace PhpTuf\ComposerStager\Domain;

interface StagerInterface
{
    /**
     * @param string[] $composerCommand The Composer command parts exactly as
     *   they would be typed in the terminal. There's no need to escape them in
     *   any way, only to separate them. Example:
     *
     * @code{.php}
     *   $command = [
     *     // "composer" is implied.
     *     'require',
     *     'lorem/ipsum:"^1 || ^2"',
     *     '--with-all-dependencies',
     *   ];
     * @endcode
     *
     * @param string $stagingDir
     * @param callable|null $callback An optional PHP callback to run whenever
     *   there is some output available on STDOUT or STDERR.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException If the
     *   given Composer command is invalid.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException If the
     *   command process doesn't terminate successfully.
     */
    public function stage(array $composerCommand, string $stagingDir, ?callable $callback = null): void;
}
