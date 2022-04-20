<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as SymfonyExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class Filesystem implements FilesystemInterface
{
    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $symfonyFilesystem;

    public function __construct(SymfonyFilesystem $symfonyFilesystem)
    {
        $this->symfonyFilesystem = $symfonyFilesystem;
    }

    public function copy(string $source, string $destination): void
    {
        try {
            $this->symfonyFilesystem->copy($source, $destination, true);
        } catch (SymfonyIOException $e) {
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

    public function isWritable(string $path): bool
    {
        return is_writable($path); // @codeCoverageIgnore
    }

    public function mkdir(string $path): void
    {
        try {
            $this->symfonyFilesystem->mkdir($path);
        } catch (SymfonyIOException $e) {
            throw new IOException(sprintf(
                'Failed to create directory at "%s".',
                $path
            ), (int) $e->getCode(), $e);
        }
    }

    /** @todo Do something with $callback. */
    public function remove(
        string $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        try {
            // Symfony Filesystem doesn't have a builtin mechanism for setting a
            // timeout, so we have to enforce it ourselves.
            set_time_limit((int) $timeout);

            $this->symfonyFilesystem->remove($path);
        } catch (SymfonyExceptionInterface $e) {
            throw new IOException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
