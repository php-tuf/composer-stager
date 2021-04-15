<?php

namespace PhpTuf\ComposerStager\Console;

use PhpTuf\ComposerStager\Filesystem\Filesystem;

class ApplicationOptions
{
    public const ACTIVE_DIR = 'active-dir';
    public const STAGING_DIR = 'staging-dir';

    /**
     * @var string
     */
    private $activeDir;

    /**
     * @var \PhpTuf\ComposerStager\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function resolve(?string $activeDir, ?string $stagingDir): void
    {
        $this->activeDir = $this->resolveActiveDir($activeDir);
        $this->stagingDir = $this->resolveStagingDir($stagingDir);
    }

    public function getActiveDir(): ?string
    {
        return $this->activeDir;
    }

    public function getStagingDir(): ?string
    {
        return $this->stagingDir;
    }

    private function resolveActiveDir(?string $activeDir): string
    {
        if (is_null($activeDir)) {
            return $this->filesystem->getcwd();
        }
        return $activeDir;
    }

    private function resolveStagingDir(?string $stagingDir): string
    {
        if (is_null($stagingDir)) {
            return $this->filesystem->getcwd() . '/.composer_staging';
        }
        return $stagingDir;
    }
}
