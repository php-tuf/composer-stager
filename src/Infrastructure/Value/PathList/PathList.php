<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\PathList;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/** @api This class may be instantiated or directly gotten from the service container via its interface. */
final class PathList implements PathListInterface
{
    /** @var array<string> */
    private array $paths = [];

    /**
     * @param array<string> $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    public function __construct(array $paths)
    {
        $this->add($paths);
    }

    /** @return array<string> */
    public function getAll(): array
    {
        return $this->paths;
    }

    public function add(array $paths): void
    {
        $this->assertValidInput($paths);
        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * @param array<string> $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    private function assertValidInput(array $paths): void
    {
        foreach ($paths as $path) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_string($path)) {
                $given = is_object($path)
                    ? $path::class
                    : gettype($path);

                throw new InvalidArgumentException(sprintf(
                    'Paths must be strings. Given %s.',
                    $given,
                ));
            }
        }
    }
}
