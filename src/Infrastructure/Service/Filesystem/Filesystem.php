<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use Symfony\Component\Filesystem\Exception\ExceptionInterface as SymfonyExceptionInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException as SymfonyFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class Filesystem implements FilesystemInterface
{
    private PathFactoryInterface $pathFactory;

    private SymfonyFilesystem $symfonyFilesystem;

    public function __construct(PathFactoryInterface $pathFactory, SymfonyFilesystem $symfonyFilesystem)
    {
        $this->pathFactory = $pathFactory;
        $this->symfonyFilesystem = $symfonyFilesystem;
    }

    public function copy(PathInterface $source, PathInterface $destination): void
    {
        $sourceResolved = $source->resolve();
        $destinationResolved = $destination->resolve();

        if ($sourceResolved === $destinationResolved) {
            throw new LogicException(sprintf(
                'The source and destination files cannot be the same at "%s"',
                $sourceResolved,
            ));
        }

        try {
            $this->symfonyFilesystem->copy($sourceResolved, $destinationResolved, true);
        } catch (SymfonyFileNotFoundException $e) {
            throw new LogicException(sprintf(
                'The source file does not exist or is not a file at "%s"',
                $sourceResolved,
            ), $e->getCode(), $e);
        } catch (SymfonyIOException $e) {
            throw new IOException(sprintf(
                'Failed to copy "%s" to "%s"',
                $sourceResolved,
                $destinationResolved,
            ), $e->getCode(), $e);
        }
    }

    public function exists(PathInterface $path): bool
    {
        return $this->symfonyFilesystem->exists($path->resolve());
    }

    /**
     * @noinspection PhpUsageOfSilenceOperatorInspection
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function isLink(PathInterface $path): bool
    {
        // It seems intuitive to just use PHP's `is_link()` function here, but
        // it only catches symlinks, whereas this method is meant to catch hard
        // links, too. Error reporting is suppressed because using `lstat()` on
        // a non-link emits E_WARNING, which may or may not throw an exception
        // depending on the error_reporting configuration.
        $lstat = @lstat($path->resolve());

        // Path does not exist.
        if ($lstat === false) {
            return false;
        }

        $mode = $lstat['mode'];
        $mode = (int) decoct($mode);
        $mode = (int) floor($mode / 10_000) * 10_000;

        // Path is a symlink.
        if ($mode === 120_000) {
            return true;
        }

        // Path is a hard link.
        if ($lstat['nlink'] > 1) {
            return true;
        }

        return false;
    }

    public function isWritable(PathInterface $path): bool
    {
        return is_writable($path->resolve()); // @codeCoverageIgnore
    }

    public function mkdir(PathInterface $path): void
    {
        $pathResolved = $path->resolve();

        try {
            $this->symfonyFilesystem->mkdir($pathResolved);
        } catch (SymfonyIOException $e) {
            throw new IOException(sprintf(
                'Failed to create directory at "%s"',
                $pathResolved,
            ), $e->getCode(), $e);
        }
    }

    public function readLink(PathInterface $path): PathInterface
    {
        if (!$this->isLink($path)) {
            throw new IOException(sprintf('The path does not exist or is not a link at "%s"', $path->resolve()));
        }

        $target = readlink($path->resolve());
        assert(is_string($target));

        return $this->pathFactory::create($target);
    }

    public function remove(
        PathInterface $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void {
        try {
            // Symfony Filesystem doesn't have a builtin mechanism for setting a
            // timeout, so we have to enforce it ourselves.
            set_time_limit((int) $timeout);

            $this->symfonyFilesystem->remove($path->resolve());
        } catch (SymfonyExceptionInterface $e) {
            throw new IOException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
