<?php

namespace PhpTuf\ComposerStager\Infrastructure\Filesystem;

use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\IOException;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @internal
 */
final class Filesystem implements FilesystemInterface
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $symfonyFilesystem;

    public function __construct(SymfonyFilesystem $symfonyFilesystem)
    {
        $this->symfonyFilesystem = $symfonyFilesystem;
    }

    public function copy(string $source, string $destination): void
    {
        try {
            $this->symfonyFilesystem->copy($source, $destination, true);
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            throw new IOException(sprintf(
                'Failed to copy "%s" to "%s".',
                $source,
                $destination
            ), (int) $e->getCode(), $e);
        }
    }

    public function exists(string $path): bool
    {
        return $this->symfonyFilesystem->exists($path);
    }

    public function getcwd(): string
    {
        $cwd = getcwd();
        if ($cwd === false) {
            throw new IOException('Cannot access the current working directory.'); // @codeCoverageIgnore
        }
        return $cwd;
    }

    public function isWritable(string $path): bool
    {
        return is_writable($path); // @codeCoverageIgnore
    }

    public function mkdir(string $path): void
    {
        try {
            $this->symfonyFilesystem->mkdir($path);
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            throw new IOException(sprintf(
                'Failed to create directory at "%s".',
                $path
            ), (int) $e->getCode(), $e);
        }
    }

    public function remove(
        string $path,
        ?OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        try {
            // Symfony Filesystem doesn't have a builtin mechanism for setting a
            // timeout, so we have to enforce it ourselves.
            set_time_limit((int) $timeout);

            $this->symfonyFilesystem->remove($path);
        } catch (ExceptionInterface $e) {
            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
