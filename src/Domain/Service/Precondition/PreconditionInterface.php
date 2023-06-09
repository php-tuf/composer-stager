<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * Defines a precondition for a domain operation and how to verify it.
 *
 * @package Precondition
 *
 * @api
 */
interface PreconditionInterface
{
    /**
     * Gets the name.
     *
     * E.g., "Example dependency".
     */
    public function getName(): TranslatableInterface;

    /**
     * Gets a short description.
     *
     * This should probably be kept to one or two sentences, e.g., "The example
     * dependency is required in order to perform some relevant action."
     */
    public function getDescription(): TranslatableInterface;

    /**
     * Gets a short status message.
     *
     * This reflects the actual status of the precondition at runtime and may
     * include details for resolving an unfulfilled precondition, e.g., "The
     * example dependency is ready," or if unfulfilled, "The example dependency
     * cannot be found. Make sure it's installed." If the precondition has
     * unfulfilled leaves, the status message from the first one will be returned.
     */
    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): TranslatableInterface;

    /** Determines whether the precondition is fulfilled. */
    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool;

    /**
     * Asserts that the precondition is fulfilled.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     *   If the precondition is unfulfilled.
     */
    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void;

    /**
     * Returns a flat array of all concrete preconditions in the contained tree.
     *
     * This may be valuable for a creating a status report, for example.
     *
     * @return array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface>
     */
    public function getLeaves(): array;
}
