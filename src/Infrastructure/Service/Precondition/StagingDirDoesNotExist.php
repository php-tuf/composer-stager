<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
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
            throw new PreconditionException(
                $this,
                $this->t('The staging directory already exists.'),
            );
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The staging directory does not already exist.');
    }
}
