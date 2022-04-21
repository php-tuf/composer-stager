<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;

/**
 * Provides basic utilities for interacting with the file system.
 */
interface FilesystemInterface
{
    /**
     * Copies a given file from one place to another.
     *
     * If the file already exists at the destination it will be overwritten.
     * Copying directories is not supported.
     *
     * @param string $source
     *   The file to copy, as an absolute path or relative to the current
     *   working directory as returned by `getcwd()` at runtime, e.g.,
     *   "/var/www/source" or "source".
     * @param string $destination
     *   The file to copy to, as an absolute path or relative to the current
     *   working directory as returned by `getcwd()` at runtime, e.g.,
     *   "/var/www/destination" or "destination". If it does not exist it will
     *   be created.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If copying fails.
     */
    public function copy(string $source, string $destination): void;

    /**
     * Determines whether the given path exists.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory as returned by
     *   `getcwd()` at runtime, e.g., "/var/www/public" or "public".
     */
    public function exists(string $path): bool;

    /**
     * Determines whether the given path is writable.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory as returned
     *   by `getcwd()` at runtime, e.g., "/var/www/public" or "public".
     */
    public function isWritable(string $path): bool;

    /**
     * Recursively creates a directory at the given path.
     *
     * @param string $path
     *   The directory to create.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If creation fails.
     */
    public function mkdir(string $path): void;

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
