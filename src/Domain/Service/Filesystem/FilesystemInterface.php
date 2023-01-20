<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/** Provides basic utilities for interacting with the file system. */
interface FilesystemInterface
{
    /**
     * Copies a given file from one place to another.
     *
     * If the file already exists at the destination it will be overwritten.
     * Copying directories is not supported.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $source
     *   The file to copy.
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $destination
     *   The file to copy to. If it does not exist it will be created.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the file cannot be copied.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the source file does not exist, is not actually a file, or is the
     *   same as the destination.
     */
    public function copy(PathInterface $source, PathInterface $destination): void;

    /**
     * Determines whether the given path exists.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $path
     *   A path to test.
     */
    public function exists(PathInterface $path): bool;

    /**
     * Determines whether the given path is writable.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $path
     *   A path to test.
     */
    public function isWritable(PathInterface $path): bool;

    /**
     * Recursively creates a directory at the given path.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $path
     *   The directory to create.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the directory cannot be created.
     */
    public function mkdir(PathInterface $path): void;

    /**
     * Returns the target of a symbolic link.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $path
     *   The link path.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the the path is not a symbolic link or cannot be read.
     */
    public function readlink(PathInterface $path): string;

    /**
     * Removes the given path.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $path
     *   A path to remove.
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the file cannot be removed.
     */
    public function remove(
        PathInterface $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void;
}
