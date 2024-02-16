<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Path\Service\PathHelperInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class RobocopyFileSyncer implements FileSyncerInterface
{
    use TranslatableAwareTrait;

    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly ExecutableFinderInterface $executableFinder,
        private readonly FilesystemInterface $filesystem,
        private readonly PathHelperInterface $pathHelper,
        private readonly PathListFactoryInterface $pathListFactory,
        private readonly ProcessFactoryInterface $processFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?OutputCallbackInterface $callback = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        $this->environment->setTimeLimit($timeout);

        $exclusions ??= $this->pathListFactory->create();

        $this->assertRobocopyIsAvailable();
        $this->assertSourceAndDestinationAreDifferent($source, $destination);
        $this->assertSourceExists($source);

        $this->runCommand($exclusions, $source->absolute(), $destination->absolute(), $destination, $callback);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertRobocopyIsAvailable(): void
    {
        $this->executableFinder->find('robocopy');
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertSourceAndDestinationAreDifferent(PathInterface $source, PathInterface $destination): void
    {
        if ($source->absolute() === $destination->absolute()) {
            throw new LogicException(
                $this->t(
                    'The source and destination directories cannot be the same at %path',
                    $this->p(['%path' => $source->absolute()]),
                    $this->d()->exceptions(),
                ),
            );
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function assertSourceExists(PathInterface $source): void
    {
        if (!$this->filesystem->fileExists($source)) {
            throw new LogicException($this->t(
                'The source directory does not exist at %path',
                $this->p(['%path' => $source->absolute()]),
                $this->d()->exceptions(),
            ));
        }

        if (!$this->filesystem->isDir($source)) {
            throw new LogicException($this->t(
                'The source directory is not actually a directory at %path',
                $this->p(['%path' => $source->absolute()]),
                $this->d()->exceptions(),
            ));
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     */
    private function runCommand(
        ?PathListInterface $exclusions,
        string $sourceAbsolute,
        string $destinationAbsolute,
        PathInterface $destination,
        ?OutputCallbackInterface $callback,
    ): void {
        $sourceAbsolute = $this->pathHelper->canonicalize($sourceAbsolute);
        $destinationAbsolute = $this->pathHelper->canonicalize($destinationAbsolute);

        $this->ensureDestinationDirectoryExists($destination);
        $command = $this->buildCommand($exclusions, $sourceAbsolute, $destinationAbsolute);

        $process = $this->processFactory->create($command);

        $process->run($callback);
    }

    /**
     * Ensures that the destination directory exists. This has no effect if it already does.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\IOException
     */
    private function ensureDestinationDirectoryExists(PathInterface $destination): void
    {
        $this->filesystem->mkdir($destination);
    }

    /** @return array<string> */
    private function buildCommand(
        ?PathListInterface $exclusions,
        string $sourceAbsolute,
        string $destinationAbsolute,
    ): array {
        $exclusions ??= $this->pathListFactory->create();
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $exclusions = $exclusions->getAll();

        $command = [
            'robocopy',
            $sourceAbsolute,
            $destinationAbsolute,
            '/MIR',
        ];

        // Prevent infinite recursion if the source is inside the destination.
        if ($this->isDescendant($sourceAbsolute, $destinationAbsolute)) {
            $exclusions[] = self::getRelativePath($destinationAbsolute, $sourceAbsolute);
        }

        if ($exclusions !== []) {
            $command[] = '/XD';
            $command[] = implode(' ', $exclusions);
            $command[] = '/XF';
            $command[] = implode(' ', $exclusions);
        }

        return $command;
    }

    private function isDescendant(string $descendant, string $ancestor): bool
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        return str_starts_with($descendant, $ancestor);
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        $ancestor .= DIRECTORY_SEPARATOR;

        if (str_starts_with($path, $ancestor)) {
            return substr($path, strlen($ancestor));
        }

        return $path;
    }
}
