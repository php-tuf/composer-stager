<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @api
 */
abstract class AbstractPreconditionsTree implements PreconditionInterface
{
    // This isn't used directly in this class--it's for children.
    use TranslatableAwareTrait;

    /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> */
    private readonly array $children;

    /** Gets a status message for when the precondition is fulfilled. */
    abstract protected function getFulfilledStatusMessage(): TranslatableInterface;

    /**
     * The order in which children are evaluated is unspecified and should not be depended upon. There is no
     * guarantee that the order they are supplied in will have, or continue to have, any determinate effect.
     *
     * @see https://github.com/php-tuf/composer-stager/issues/75
     */
    public function __construct(TranslatableFactoryInterface $translatableFactory, PreconditionInterface ...$children)
    {
        $this->setTranslatableFactory($translatableFactory);
        $this->children = $children;
    }

    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): TranslatableInterface {
        try {
            $this->assertIsFulfilled($activeDir, $stagingDir, $exclusions);
        } catch (PreconditionException $e) {
            return $e->getTranslatableMessage();
        }

        return $this->getFulfilledStatusMessage();
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
