<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class StagingDirDoesNotExist extends AbstractPrecondition implements StagingDirDoesNotExistInterface
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
        return $this->t('Staging directory does not exist');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The staging directory must not already exist before beginning the staging process.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if ($this->filesystem->exists($stagingDir)) {
            throw new PreconditionException($this, $this->t(
                'The staging directory already exists.',
                null,
                $this->d()->exceptions(),
            ));
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The staging directory does not already exist.');
    }
}
