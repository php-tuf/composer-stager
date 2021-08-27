<?php

namespace PhpTuf\ComposerStager\Infrastructure\Filesystem;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Provides basic utilities for interacting with the file system.
 */
interface FilesystemInterface
{
    /**
     * Copies a file.
     *
     * If the file already exists at the "to" path it will be overwritten.
     *
     * @param string $fromPath
     *   The file to copy, as an absolute path or relative to the current
     *   working directory (CWD), e.g., "/var/www/from" or "from".
     * @param string $toPath
     *   The file to copy to, as an absolute path or relative to the current
     *   working directory (CWD), e.g., "/var/www/to" or "to". If it does
     *   not exist it will be created.
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the operation is unsuccessful.
     */
    public function copy(string $fromPath, string $toPath): void;

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
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If there is a failure. For example, on some Unix variants, this check
     *   will fail if any one of the parent directories does not have the
     *   readable or search mode set, even if the current directory does.
     */
    public function getcwd(): string;

    /**
     * Determines whether the given path is a directory.
     *
     * Consistent with PHP's own behavior on this point, a symlink will be
     * followed and treated like the path it points to. In other words, a
     * symlink that points to a directory will return true.
     *
     * @see https://www.php.net/manual/en/function.is-dir.php
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     */
    public function isDir(string $path): bool;

    /**
     * Determines whether the given path is a file.
     *
     * Consistent with PHP's own behavior on this point, a symlink will be
     * followed and treated like the path it points to. In other words, a
     * symlink that points to a file will return true.
     *
     * @see https://www.php.net/manual/en/function.is-file.php
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     */
    public function isFile(string $path): bool;

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
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
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
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
