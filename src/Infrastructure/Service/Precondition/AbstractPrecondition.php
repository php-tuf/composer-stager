<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

abstract class AbstractPrecondition implements PreconditionInterface
{
    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): string;

    /** Gets a status message for when the precondition is unfulfilled. */
    abstract protected function getUnfulfilledStatusMessage(): string;

    final public function assertIsFulfilled(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        if (!$this->isFulfilled($activeDir, $stagingDir)) {
            throw new PreconditionException($this, $this->getUnfulfilledStatusMessage());
        }
    }

    public function getStatusMessage(PathInterface $activeDir, PathInterface $stagingDir): string
    {
        return $this->isFulfilled($activeDir, $stagingDir)
            ? $this->getFulfilledStatusMessage()
            : $this->getUnfulfilledStatusMessage();
    }
}
