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
     *   If the source directory doesn't exist or copying fails.
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
     *   If creation fails.
     */
    public function mkdir(PathInterface $path): void;

    /**
     * Removes the given path.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory as returned by
     *   `getcwd()` at runtime, e.g., "/var/www/public" or "public".
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If removal fails.
     */
    public function remove(
        string $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void;
}
