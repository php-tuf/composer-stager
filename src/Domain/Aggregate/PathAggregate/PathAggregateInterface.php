<?php

namespace PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate;

/**
 * Aggregates path value objects.
 */
interface PathAggregateInterface
{
    /**
     * Returns all aggregated path value objects.
     *
     * @return \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[]
     */
    public function getAll(): array;
}
