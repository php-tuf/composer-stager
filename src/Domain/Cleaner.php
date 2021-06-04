<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;

final class Cleaner implements CleanerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function clean(string $stagingDir): void
    {
        if (!$this->directoryExists($stagingDir)) {
            throw new DirectoryNotFoundException($stagingDir, 'The staging directory does not exist at "%s"');
        }

        try {
            $this->filesystem->remove($stagingDir);
        } catch (\Symfony\Component\Filesystem\Exception\ExceptionInterface $e) {
            throw new IOException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function directoryExists(string $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir);
    }
}
