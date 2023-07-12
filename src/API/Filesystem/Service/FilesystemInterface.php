<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Filesystem\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;

/**
 * Provides basic utilities for interacting with the file system.
 *
 * @package Filesystem
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface FilesystemInterface
{
    /**
     * Copies a given file from one place to another.
     *
     * If the file already exists at the destination it will be overwritten.
     * Copying directories is not supported.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $source
     *   The file to copy.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $destination
     *   The file to copy to. If it does not exist it will be created.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the file cannot be copied.
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the source file does not exist, is not actually a file, or is the
     *   same as the destination.
     */
    public function copy(PathInterface $source, PathInterface $destination): void;

    /**
     * Determines whether the given path exists.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     */
    public function exists(PathInterface $path): bool;

    /**
     * Determines whether the given path is a directory.
     *
     * Unlike PHP's built-in is_dir() function, this method distinguishes
     * between directories and LINKS to directories. In other words, if the path
     * is a link, even if the target is a directory, this method will return false.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the path exists and is a directory.
     */
    public function isDir(PathInterface $path): bool;

    /**
     * Determines whether the given directory is empty.
     *
     * @return bool
     *   Returns true if the directory is empty, false otherwise.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the directory does not exist or is not actually a directory.
     */
    public function isDirEmpty(PathInterface $path): bool;

    /**
     * Determines whether the given path is a regular file.
     *
     * Unlike PHP's built-in is_file() function, this method distinguishes
     * between regular files and LINKS to files. In other words, if the path is
     * a link, even if the target is a regular file, this method will return false.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the path exists and is a regular file.
     */
    public function isFile(PathInterface $path): bool;

    /**
     * Determines whether the given path is a hard link.
     *
     * Symbolic links (symlinks) are distinct from hard links and do not count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a hard link (not a symlink)
     *   false otherwise.
     */
    public function isHardLink(PathInterface $path): bool;

    /**
     * Determines whether the given path is a link.
     *
     * Symbolic links (symlinks) and hard links both count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a link, false otherwise.
     */
    public function isLink(PathInterface $path): bool;

    /**
     * Determines whether the given path is a symbolic link.
     *
     * Hard links are distinct from symbolic links (symlinks) and do not count.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     *
     * @return bool
     *   Returns true if the filename exists and is a symlink (not a hard link),
     *   false otherwise.
     */
    public function isSymlink(PathInterface $path): bool;

    /**
     * Determines whether the given path is writable.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to test.
     */
    public function isWritable(PathInterface $path): bool;

    /**
     * Recursively creates a directory at the given path.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The directory to create.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the directory cannot be created.
     */
    public function mkdir(PathInterface $path): void;

    /**
     * Returns the target of a symbolic link.
     *
     * Hard links are not included and will throw an exception. Consider using
     * ::isSymlink() first.
     *
     * Note: PHP does not distinguish between absolute and relative links on
     * Windows, so the returned path object there will be based on a canonicalized,
     * absolute raw path string. In other words, ALL link paths on Windows will
     * behave like absolute links, whether they really are or not.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   The link path.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the path is not a symbolic link (symlink) or cannot be read. Hard
     *   links are distinct from symlinks and will still throw an exception.
     */
    public function readLink(PathInterface $path): PathInterface;

    /**
     * Removes the given path.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $path
     *   A path to remove.
     * @param \PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     *   If the file cannot be removed.
     */
    public function remove(
        PathInterface $path,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void;
}
