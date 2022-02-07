<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

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
        // It is technically possible for getcwd() to fail and return false. (For
        // example, on some Unix variants, this check will fail if any one of the
        // parent directories does not have the readable or search mode set, even
        // if the current directory does.) But the likelihood is probably so slight
        // that it hardly seems worth cluttering up client code handling theoretical
        // IO exceptions. Cast the return value to a string for the purpose of
        // static analysis and move on.
        return (string) getcwd();
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
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
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
