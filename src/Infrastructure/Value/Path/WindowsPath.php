<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

/**
 * Handles a Windows filesystem path string.
 *
 * Don't instantiate this class directory--use the path factory.
 *
 * @see \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 */
final class WindowsPath extends AbstractPath
{
    public function isAbsolute(): bool
    {
        if ($this->isAbsoluteFromSpecificDrive($this->path)) {
            return true;
        }

        if ($this->isAbsoluteFromCurrentDrive($this->path)) {
            return true;
        }

        // If the path doesn't match known, supported patterns (there are technically
        // others), assume it to be relative, e.g., `Example' or `..\Example`.
        return false;
    }

    /**
     * Windows path rules are complex. They are only partially implemented here.
     *
     * @see https://docs.microsoft.com/en-us/dotnet/standard/io/file-path-formats
     */
    protected function doResolve(string $basePath): string
    {
        $path = $this->path;

        if ($this->isAbsoluteFromSpecificDrive($path)) {
            return $this->normalizeAbsoluteFromSpecificDrive($path);
        }

        if ($this->isAbsoluteFromCurrentDrive($path)) {
            return $this->normalizeAbsoluteFromCurrentDrive($path, $basePath);
        }

        // If the path doesn't match known, supported patterns (there are technically
        // others), assume it to be relative, e.g., `Example' or `..\Example`.
        return $this->getAbsoluteFromRelative($path, $basePath);
    }

    /** Determines whether the path is absolute from a specified drive, e.g., "C:\Program Files\Example". */
    private function isAbsoluteFromSpecificDrive(string $path): bool
    {
        // A Windows drive name is a single letter followed by a colon. An
        // absolute reference to it is followed by a directory separator. The
        // following regex accepts either directory separator, mostly for easier
        // development and smoke testing on non-Windows systems.
        // @see https://docs.microsoft.com/en-us/dotnet/standard/io/file-path-formats#traditional-dos-paths
        return preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1;
    }

    private function normalizeAbsoluteFromSpecificDrive(string $path): string
    {
        $driveRoot = substr($path, 0, 3);

        return $this->normalize($path, $driveRoot);
    }

    /** Determines whether the path is absolute from the current drive, e.g., "\Program Files\Example". */
    private function isAbsoluteFromCurrentDrive(string $path): bool
    {
        // An absolute path to the current drive begins with a directory separator.
        // Again, both kinds are supported for easier development on non-Windows systems.
        return preg_match('/^[\\\\\/]/', $path) === 1;
    }

    private function normalizeAbsoluteFromCurrentDrive(string $path, string $basePath): string
    {
        // Get the current drive from the base path.
        $driveName = substr($basePath, 0, 2);

        // Prefix the normalized path with it and return.
        return $driveName . $this->normalize($path);
    }

    private function getAbsoluteFromRelative(string $path, string $basePath): string
    {
        // Make the path absolute by prefixing the base path.
        $path = $basePath . DIRECTORY_SEPARATOR . $path;

        // Normalize and return.
        $driveRoot = substr($path, 0, 3);

        return $this->normalize($path, $driveRoot);
    }
}
