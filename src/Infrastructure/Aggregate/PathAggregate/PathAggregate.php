<?php

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;

final class PathAggregate implements PathAggregateInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[]
     */
    private $paths;

    /**
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[] $paths
     *
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    public function __construct(array $paths)
    {
        $this->assertValidInput($paths);
        $this->paths = $paths;
    }

    public function getAll(): array
    {
        return $this->paths;
    }

    /**
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[] $paths
     *
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function assertValidInput(array $paths): void
    {
        foreach ($paths as $path) {
            if (!$path instanceof PathInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Paths must implement %s. Given %s.',
                    PathInterface::class,
                    var_export($path, true)
                ));
            }
        }
    }
}
