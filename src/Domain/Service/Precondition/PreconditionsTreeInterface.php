<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

/**
 * Contains a set of preconditions for a domain operation.
 *
 * @api
 */
interface PreconditionsTreeInterface extends PreconditionInterface
{
    /**
     * Returns a flat array of all concrete preconditions in the contained tree.
     *
     * This may be valuable for a creating a status report, for example.
     *
     * @return array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface>
     */
    public function getLeaves(): array;
}
