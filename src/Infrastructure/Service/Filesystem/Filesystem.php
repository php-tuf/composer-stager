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
    private const PATH_DOES_NOT_EXIST = 'PATH_DOES_NOT_EXIST';

    private const PATH_IS_DIRECTORY = 'PATH_IS_DIRECTORY';

    private const PATH_IS_HARD_LINK = 'PATH_IS_HARD_LINK';

    private const PATH_IS_OTHER_TYPE = 'PATH_IS_OTHER_TYPE';

    private const PATH_IS_REGULAR_FILE = 'PATH_IS_REGULAR_FILE';

    private const PATH_IS_SYMLINK = 'PATH_IS_SYMLINK';

    public function __construct(
        private readonly PathFactoryInterface $pathFactory,
        private readonly SymfonyFilesystem $symfonyFilesystem,
    ) {
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
        return $this->getFileType($path) !== self::PATH_DOES_NOT_EXIST;
    }

    public function isDir(PathInterface $path): bool
    {
        return $this->getFileType($path) === self::PATH_IS_DIRECTORY;
    }

    public function isFile(PathInterface $path): bool
    {
        return $this->getFileType($path) === self::PATH_IS_REGULAR_FILE;
    }

    public function isHardLink(PathInterface $path): bool
    {
        return $this->getFileType($path) === self::PATH_IS_HARD_LINK;
    }

    public function isLink(PathInterface $path): bool
    {
        return in_array($this->getFileType($path), [
            self::PATH_IS_HARD_LINK,
            self::PATH_IS_SYMLINK,
        ], true);
    }

    public function isSymlink(PathInterface $path): bool
    {
        return $this->getFileType($path) === self::PATH_IS_SYMLINK;
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
        if (!$this->isSymlink($path)) {
            throw new IOException(sprintf('The path does not exist or is not a symlink at "%s"', $path->resolve()));
        }

        $target = readlink($path->resolve());
        assert(is_string($target));

        // Resolve the target relative to the link's parent directory, not the CWD of the PHP process at runtime.
        $cwd = $this->pathFactory::create('..', $path);

        return $this->pathFactory::create($target, $cwd);
    }

    public function remove(
        PathInterface $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
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

    /**
     * @noinspection PhpUsageOfSilenceOperatorInspection
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    private function getFileType(PathInterface $path): string
    {
        // A single call to `lstat()` should be cheaper than individual calls to `file_exists()`
        // and `is_link()`, etc., not to mention being the only way to detect hard links at all.
        // Error reporting is suppressed because using `lstat()` on a non-link emits E_WARNING,
        // which may or may not throw an exception depending on error_reporting configuration.
        $lstat = @lstat($path->resolve());

        if ($lstat === false) {
            return self::PATH_DOES_NOT_EXIST;
        }

        // @see https://www.php.net/manual/en/function.stat.php
        $mode = $lstat['mode'];
        $mode = (int) decoct($mode);
        $mode = (int) floor($mode / 10_000) * 10_000;

        if ($mode === 120_000) {
            return self::PATH_IS_SYMLINK;
        }

        if ($mode === 40_000) {
            return self::PATH_IS_DIRECTORY;
        }

        if ($lstat['nlink'] > 1) {
            return self::PATH_IS_HARD_LINK;
        }

        if ($mode === 100_000) {
            return self::PATH_IS_REGULAR_FILE;
        }

        // This is unlikely to happen in practice, and it's impractical to test.
        return self::PATH_IS_OTHER_TYPE; // @codeCoverageIgnore
    }
}
