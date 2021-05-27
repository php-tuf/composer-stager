<?php

namespace PhpTuf\ComposerStager\Domain;

interface CleanerInterface
{
    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function clean(string $stagingDir): void;

    public function directoryExists(string $stagingDir): bool;
}
