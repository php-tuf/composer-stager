<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;

/**
 * @package Precondition
 *
 * @api
 */
abstract class AbstractPrecondition implements PreconditionInterface
{
    final public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        if (!$this->isFulfilled($activeDir, $stagingDir, $exclusions)) {
            throw new PreconditionException($this, $this->getUnfulfilledStatusMessage());
        }
    }

    final public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): string {
        return $this->isFulfilled($activeDir, $stagingDir, $exclusions)
            ? $this->getFulfilledStatusMessage()
            : $this->getUnfulfilledStatusMessage();
    }

    final public function getLeaves(): array
    {
        return [$this];
    }

    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): string;

    /** Gets a status message for when the precondition is unfulfilled. */
    abstract protected function getUnfulfilledStatusMessage(): string;
}
