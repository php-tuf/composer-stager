<?php

namespace PhpTuf\ComposerStager\Infrastructure\Filesystem;

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

    public function remove(string $path): void
    {
        try {
            $this->symfonyFilesystem->remove($path);
        } catch (ExceptionInterface $e) {
            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
