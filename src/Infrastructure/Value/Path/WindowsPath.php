<?php

namespace PhpTuf\ComposerStager\Infrastructure\Value\Path;

final class WindowsPath extends AbstractPath
{
    /**
     * Windows path rules are complex. They are only partially implemented here.
     * @see https://docs.microsoft.com/en-us/dotnet/standard/io/file-path-formats
     */
    public function getAbsolute(): string
    {
        $path = $this->path;

        // If the path is absolute from a specified drive, e.g., `C:\Program Files\Example`.
        if ($this->isAbsoluteFromSpecificDrive($path)) {
            return $this->normalizeAbsoluteFromSpecificDrive($path);
        }

        // If the path is absolute from the current drive, e.g., `\Program Files\Example`.
        if ($this->isAbsoluteFromCurrentDrive($path)) {
            return $this->normalizeAbsoluteFromCurrentDrive($path);
        }

        // Otherwise, the path is assumed to be relative--there are technically
        // other possibilities--e.g., `Example' or `..\Example`.
        return $this->getAbsoluteFromRelative($path);
    }

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

    private function isAbsoluteFromCurrentDrive(string $path): bool
    {
        // An absolute path to the current drive begins with a directory separator.
        // Again, both kinds are supported for easier development on non-Windows systems.
        return preg_match('/^[\\\\\/]/', $path) === 1;
    }

    private function normalizeAbsoluteFromCurrentDrive(string $path): string
    {
        // Get the current drive from the CWD.
        $cwd = $this->filesystem->getcwd();
        $driveName = substr($cwd, 0, 2);

        // Prefix the normalized path with it and return.
        return $driveName . $this->normalize($path);
    }

    private function getAbsoluteFromRelative(string $path): string
    {
        // Make the path absolute by prefixing the CWD.
        $cwd = $this->filesystem->getcwd();
        $path = $cwd . DIRECTORY_SEPARATOR . $path;

        // Normalize and return.
        $driveRoot = substr($path, 0, 3);
        return $this->normalize($path, $driveRoot);
    }
}
