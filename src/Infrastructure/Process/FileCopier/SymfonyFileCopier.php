<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

/**
 * @internal
 */
final class SymfonyFileCopier implements SymfonyFileCopierInterface
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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

            $iterator = $this->createIterator($from);
            $this->filesystem->mirror($from, $to, $iterator);
        } catch (IOException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     */
    private function createIterator(string $from): RecursiveCallbackFilterIterator
    {
        try {
            $directoryIterator = new RecursiveDirectoryIterator($from);
        } catch (UnexpectedValueException $e) {
            throw new DirectoryNotFoundException($from, 'The "copy from" directory does not exist at "%s"', (int) $e->getCode(), $e);
        }

        /** @var callable(mixed, mixed, \RecursiveIterator<mixed, mixed>):bool $callback */
        $callback = function (SplFileInfo $current): bool {
            // Ignore current and parent directories.
            if (in_array($current->getFilename(), ['.', '..'], true)) {
                return false;
            }

            return true;
        };

        return new RecursiveCallbackFilterIterator($directoryIterator, $callback);
    }
}
