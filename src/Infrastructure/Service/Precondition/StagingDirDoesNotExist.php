<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class StagingDirDoesNotExist extends AbstractPrecondition implements StagingDirDoesNotExistInterface
{
    public function __construct(private readonly FilesystemInterface $filesystem)
    {
    }

    public function getName(): string
    {
        return 'Staging directory does not exist';
    }

    public function getDescription(): string
    {
        return 'The staging directory must not already exist before beginning the staging process.';
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        return !$this->filesystem->exists($stagingDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory does not already exist.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory already exists.';
    }
}
