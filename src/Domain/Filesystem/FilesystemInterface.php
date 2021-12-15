<?php

namespace PhpTuf\ComposerStager\Domain\Filesystem;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;

/**
 * Provides basic utilities for interacting with the file system.
 */
interface FilesystemInterface
{
    /**
     * Copies a file from one place to another.
     *
     * If the file already exists at the destination it will be overwritten.
     *
     * @param string $source
     *   The file to copy, as an absolute path or relative to the current
     *   working directory (CWD), e.g., "/var/www/source" or "source".
     * @param string $destination
     *   The file to copy to, as an absolute path or relative to the current
     *   working directory (CWD), e.g., "/var/www/destination" or "destination".
     *   If it does not exist it will be created.
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the operation is unsuccessful.
     */
    public function copy(string $source, string $destination): void;

    /**
     * Determines whether the given path exists.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     *
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Gets the current working directory (CWD) on success.
     */
    public function getcwd(): string;

    /**
     * Determines whether the given path is writable.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     */
    public function isWritable(string $path): bool;

    /**
     * Recursively creates a directory at the given path.
     *
     * @param string $path
     *   The directory to create.
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If creation fails.
     */
    public function mkdir(string $path): void;

    /**
     * Removes the given path.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     * @param \PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If removal fails.
     */
    public function remove(
        string $path,
        ?OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
