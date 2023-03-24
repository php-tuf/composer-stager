<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/**
 * Defines a precondition for a domain operation and how to verify it.
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
     * This reflects the actual status of the precondition at runtime and may
     * include details for resolving an unfulfilled precondition, e.g., "The
     * example dependency is ready," or if unfulfilled, "The example dependency
     * cannot be found. Make sure it's installed."
     */
    public function getStatusMessage(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        TranslationInterface $translation,
        ?PathListInterface $exclusions = null,
    ): string;

    /** Determines whether the precondition is fulfilled. * * @param \PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface $translation*/
    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        TranslationInterface $translation,
        ?PathListInterface $exclusions = null,
    ): bool;

    /**
     * Asserts that the precondition is fulfilled.
     *
     * @param \PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface $translation *
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     *   If the precondition is unfulfilled.
     */
    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        TranslationInterface $translation,
        ?PathListInterface $exclusions = null,
    ): void;
}
