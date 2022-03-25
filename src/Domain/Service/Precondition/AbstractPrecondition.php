<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

abstract class AbstractPrecondition implements PreconditionInterface
{
    /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> */
    private $children;

    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): string;

    /** Gets a status message for when the precondition is unfulfilled. */
    abstract protected function getUnfulfilledStatusMessage(): string;

    public function __construct(PreconditionInterface ...$children)
    {
        $this->children = $children;
    }

    public function getStatusMessage(PathInterface $activeDir, PathInterface $stagingDir): string
    {
        return $this->isFulfilled($activeDir, $stagingDir)
            ? $this->getFulfilledStatusMessage()
            : $this->getUnfulfilledStatusMessage();
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        foreach ($this->children as $child) {
            if (!$child->isFulfilled($activeDir, $stagingDir)) {
                return false;
            }
        }

        return true;
    }

    public function assertIsFulfilled(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        if (!$this->isFulfilled($activeDir, $stagingDir)) {
            throw new PreconditionException($this);
        }
    }

    /** @return array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> */
    public function getChildren(): array
    {
        // This is a leaf. Return it.
        if (count($this->children) === 0) {
            return [$this];
        }

        $leaves = [];

        // This is a branch. Get its children.
        foreach ($this->children as $child) {
            $grandchildren = $child->getChildren();

            foreach ($grandchildren as $grandchild) {
                $leaves[] = $grandchild;
            }
        }

        return $leaves;
    }
}
