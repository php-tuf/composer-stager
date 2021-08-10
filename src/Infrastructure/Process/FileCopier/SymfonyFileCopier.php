<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class SymfonyFileCopier implements SymfonyFileCopierInterface
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $finder;

    public function __construct(Filesystem $filesystem, Finder $finder)
    {
        $this->filesystem = $filesystem;
        $this->finder = $finder;
    }

    public function copy(
        string $from,
        string $to,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        try {
            // Symfony Filesystem doesn't have a builtin mechanism for setting a
            // timeout, so we have to enforce it ourselves.
            set_time_limit((int) $timeout);

            $iterator = $this->createIterator($from, $exclusions);
            $this->filesystem->mirror($from, $to, $iterator);
        } catch (IOException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string[] $exclusions
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     */
    private function createIterator(string $from, array $exclusions): Finder
    {
        try {
            $this->finder
                ->in($from)
                ->filter(function (SplFileInfo $current) use ($from, $exclusions): bool {
                    // Make path name relative to "from" path like exclusions.
                    $pathName = $current->getPathname();
                    if (strpos($pathName, $from) === 0) {
                        // Strip the "from" path from the beginning of the path.
                        $pathName = substr($pathName, strlen($from));
                        $pathName = ltrim($pathName, DIRECTORY_SEPARATOR);
                    }

                    if (in_array($pathName, $exclusions, true)) {
                        return false;
                    }

                    return true;
                });
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException($from, 'The "copy from" directory does not exist at "%s"', (int) $e->getCode(), $e);
        }
        return $this->finder;
    }
}
