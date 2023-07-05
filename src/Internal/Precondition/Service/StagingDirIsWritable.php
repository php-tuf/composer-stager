<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactoryInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class StagingDirIsWritable extends AbstractPrecondition implements StagingDirIsWritableInterface
{
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Staging directory is writable');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The staging directory must be writable before any operations can be performed.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if (!$this->filesystem->isWritable($stagingDir)) {
            throw new PreconditionException($this, $this->t(
                'The staging directory is not writable.',
                null,
                $this->d()->exceptions(),
            ));
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The staging directory is writable.');
    }
}
