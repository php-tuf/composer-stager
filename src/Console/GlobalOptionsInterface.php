<?php

namespace PhpTuf\ComposerStager\Console;

/**
 * @internal
 */
interface GlobalOptionsInterface
{
    public const STAGING_DIR = 'staging-dir';
    public const ACTIVE_DIR = 'active-dir';

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function getDefaultActiveDir(): string;

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function getDefaultStagingDir(): string;

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function resolveActiveDir(?string $activeDir): string;

    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function resolveStagingDir(?string $stagingDir): string;
}
