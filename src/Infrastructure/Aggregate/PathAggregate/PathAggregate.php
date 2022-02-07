<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class PathAggregate implements PathAggregateInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[]
     */
    private $paths;

    /**
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[] $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
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
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface[]|array $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    private function assertValidInput(array $paths): void
    {
        foreach ($paths as $path) {
            if (!$path instanceof PathInterface) {
                $given = is_object($path) ? get_class($path) : gettype($path);
                throw new InvalidArgumentException(sprintf(
                    'Paths must implement %s. Given %s.',
                    PathInterface::class,
                    $given
                ));
            }
        }
    }
}
