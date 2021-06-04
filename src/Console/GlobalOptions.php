<?php

namespace PhpTuf\ComposerStager\Console;

use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;

/**
 * @internal
 */
final class GlobalOptions implements GlobalOptionsInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getDefaultActiveDir(): string
    {
        return $this->filesystem->getcwd();
    }

    public function getDefaultStagingDir(): string
    {
        return $this->filesystem->getcwd() . '/.composer_staging';
    }

    public function resolveActiveDir(?string $activeDir): string
    {
        if (is_null($activeDir)) {
            return $this->getDefaultActiveDir();
        }
        return $activeDir;
    }

    public function resolveStagingDir(?string $stagingDir): string
    {
        if (is_null($stagingDir)) {
            return $this->getDefaultStagingDir();
        }
        return $stagingDir;
    }
}
