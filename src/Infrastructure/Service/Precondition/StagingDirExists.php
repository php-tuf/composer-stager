<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class StagingDirExists extends AbstractPrecondition implements StagingDirExistsInterface
{
    public function __construct(private readonly FilesystemInterface $filesystem)
    {
    }

    public function getName(): string
    {
        return 'Staging directory exists'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The staging directory must exist before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        TranslationInterface $translation,
        ?PathListInterface $exclusions = null,
    ): bool {
        return $this->filesystem->exists($stagingDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory exists.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory does not exist.';
    }
}
