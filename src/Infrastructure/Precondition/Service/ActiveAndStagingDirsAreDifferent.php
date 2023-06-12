<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Precondition\Service;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 *
 * phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
 */
final class ActiveAndStagingDirsAreDifferent extends AbstractPrecondition implements ActiveAndStagingDirsAreDifferentInterface
{
    public function getName(): TranslatableInterface
    {
        return $this->t('Active and staging directories are different');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The active and staging directories cannot be the same.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if ($activeDir->resolved() === $stagingDir->resolved()) {
            throw new PreconditionException(
                $this,
                $this->t('The active and staging directories are the same.'),
            );
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active and staging directories are different.');
    }
}
