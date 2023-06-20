<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
 */
final class ActiveDirIsWritable extends AbstractPrecondition implements ActiveDirIsWritableInterface
{
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        TranslatableFactoryInterface $translatableFactory,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($translatableFactory, $translator);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Active directory is writable');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The active directory must be writable before any operations can be performed.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if (!$this->filesystem->isWritable($activeDir)) {
            throw new PreconditionException(
                $this,
                $this->t('The active directory is not writable.'),
            );
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active directory is writable.');
    }
}
