<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/**
 * Defines a precondition for a domain operation and how to verify it.
 */
interface PreconditionInterface
{
    /**
     * Gets the name.
     *
     * E.g., "Example dependency".
     */
    public function getName(): string;

    /**
     * Gets a short description.
     *
     * This should probably be kept to one or two sentences, e.g., "The example
     * dependency is required in order to perform some relevant action."
     */
    public function getDescription(): string;

    /**
     * Gets a short status message.
     *
     * This message reflects the actual status of the precondition at runtime and
     * may include details for resolving an unfulfilled precondition, e.g., "The
     * example dependency is ready," or if unfulfilled, "The example dependency
     * cannot be found. Make sure it's installed."
     */
    public function getStatusMessage(PathInterface $activeDir, PathInterface $stagingDir): string;

    /**
     * Determines whether the precondition is fulfilled.
     */
    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool;

    /**
     * Throws an exception if the precondition is not fulfilled.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     */
    public function assertIsFulfilled(PathInterface $activeDir, PathInterface $stagingDir): void;

    /**
     * Recursively gets all child preconditions, leaves only.
     *
     * This returns leaves only, i.e., only preconditions that do not themselves
     * contain other preconditions, as a flat array. An implication of this
     * behavior is that a precondition should either handle business logic OR
     * contain other preconditions. But it should not do both, which could
     * result in infinite recursion or other unexpected behavior.
     *
     * @return array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface>
     */
    public function getChildren(): array;
}
