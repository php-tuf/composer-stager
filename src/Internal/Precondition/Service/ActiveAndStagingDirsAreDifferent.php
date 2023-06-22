<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\API\Translation\Value\Domain;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class ActiveAndStagingDirsAreDifferent extends AbstractPrecondition implements
    ActiveAndStagingDirsAreDifferentInterface
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
            throw new PreconditionException($this, $this->t(
                'The active and staging directories are the same.',
                null,
                Domain::EXCEPTIONS,
            ));
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active and staging directories are different.');
    }
}
