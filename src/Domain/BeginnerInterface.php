<?php

namespace PhpTuf\ComposerStager\Domain;

interface BeginnerInterface
{
    /**
     * @param string $activeDir
     * @param string $stagingDir
     * @param callable|null $callback An optional PHP callback to run whenever
     *   there is some output available on STDOUT or STDERR.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     */
    public function begin(string $activeDir, string $stagingDir, ?callable $callback = null): void;

    public function activeDirectoryExists(string $activeDir): bool;

    public function stagingDirectoryExists(string $stagingDir): bool;
}
