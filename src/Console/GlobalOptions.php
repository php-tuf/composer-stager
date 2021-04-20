<?php

namespace PhpTuf\ComposerStager\Console;

use PhpTuf\ComposerStager\Filesystem\Filesystem;

class GlobalOptions
{
    public const ACTIVE_DIR = 'active-dir';
    public const STAGING_DIR = 'staging-dir';

    /**
     * @var \PhpTuf\ComposerStager\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function resolveActiveDir(?string $activeDir): string
    {
        if (is_null($activeDir)) {
            return $this->filesystem->getcwd();
        }
        return $activeDir;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function resolveStagingDir(?string $stagingDir): string
    {
        if (is_null($stagingDir)) {
            return $this->filesystem->getcwd() . '/.composer_staging';
        }
        return $stagingDir;
    }
}
