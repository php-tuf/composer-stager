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
abstract class AbstractPreconditionsTree implements PreconditionInterface
{
    /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> */
    private array $children;

    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): string;

    /** Gets a status message for when the precondition is unfulfilled. */
    abstract protected function getUnfulfilledStatusMessage(): string;

    /**
     * The order in which children are evaluated is unspecified and should not be depended upon. There is no
     * guarantee that the order they are supplied in will have, or continue to have, any determinate effect.
     *
     * @see https://github.com/php-tuf/composer-stager/issues/75
     */
    public function __construct(PreconditionInterface ...$children)
    {
        $this->children = $children;
    }

    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): string {
        return $this->isFulfilled($activeDir, $stagingDir, $exclusions)
            ? $this->getFulfilledStatusMessage()
            : $this->getUnfulfilledStatusMessage();
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        try {
            $this->assertIsFulfilled($activeDir, $stagingDir, $exclusions);
        } catch (PreconditionException) {
            return false;
        }

        return true;
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        foreach ($this->getLeaves() as $leaf) {
            $leaf->assertIsFulfilled($activeDir, $stagingDir, $exclusions);
        }
    }

    /** This function is non-final in case subclasses want to implement a different strategy. */
    public function getLeaves(): array
    {
        $leaves = [];

        foreach ($this->children as $child) {
            $leaves[] = $child->getLeaves();
        }

        return array_merge(...$leaves);
    }
}
