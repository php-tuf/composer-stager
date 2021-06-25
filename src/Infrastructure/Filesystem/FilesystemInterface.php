<?php

namespace PhpTuf\ComposerStager\Infrastructure\Filesystem;

/**
 * Provides basic utilities for interacting with the file system.
 */
interface FilesystemInterface
{
    /**
     * Determines whether or not the given path exists.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     *
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Gets the current working directory on success, or false on failure.
     *
     * @return string
     *   The current working directory (CWD).
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If there is a failure. On some Unix variants, this check will fail if
     *   any one of the parent directories does not have the readable or search
     *   mode set, even if the current directory does.
     */
    public function getcwd(): string;

    /**
     * Determines whether or not the given path is writable.
     *
     * @param string $filename
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     *
     * @return bool
     */
    public function isWritable(string $filename): bool;

    /**
     * Removes the given path.
     *
     * @param string $path
     *   A path as absolute or relative to the working directory (CWD), e.g.,
     *   "/var/www/public" or "public".
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If removal fails.
     */
    public function remove(string $path): void;
}
