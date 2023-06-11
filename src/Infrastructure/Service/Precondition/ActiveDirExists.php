<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ActiveDirExists extends AbstractPrecondition implements ActiveDirExistsInterface
{
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        TranslatableFactoryInterface $translatableFactory,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translatableFactory, $translator);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Active directory exists');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('There must be an active directory present before any operations can be performed.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if (!$this->filesystem->exists($activeDir)) {
            throw new PreconditionException($this, $this->t('The active directory does not exist.'));
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active directory exists.');
    }
}
