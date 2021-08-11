<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

/**
 * Creates file copiers.
 */
interface FileCopierFactoryInterface
{
    /**
     * Creates the correct file copier given available tools, e.g., rsync.
     */
    public function create(): FileCopierInterface;
}
