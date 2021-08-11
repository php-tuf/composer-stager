<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
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

            // Filter exclusions.
            $iterator = $this->finder
                ->in($from)
                ->notPath($exclusions);

            $this->filesystem->mirror($from, $to, $iterator);
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException($from, 'The "copy from" directory does not exist at "%s"', (int) $e->getCode(), $e);
        } catch (IOException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
