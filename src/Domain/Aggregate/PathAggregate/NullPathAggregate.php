<?php

namespace PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate;

final class NullPathAggregate implements PathAggregateInterface
{
    public function getAll(): array
    {
        return [];
    }
}
