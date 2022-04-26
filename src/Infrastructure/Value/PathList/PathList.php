<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\PathList;

use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class PathList implements PathListInterface
{
    /** @var array<string> */
    private $paths;

    /**
     * @param array<string> $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    public function __construct(array $paths)
    {
        $this->assertValidInput($paths);
        $this->paths = $paths;
    }

    /** @return array<string> */
    public function getAll(): array
    {
        return $this->paths;
    }

    /**
     * @param array<mixed> $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    private function assertValidInput(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path)) {
                $given = is_object($path)
                    ? get_class($path)
                    : gettype($path);
                throw new InvalidArgumentException(sprintf(
                    'Paths must be strings. Given %s.',
                    $given
                ));
            }
        }
    }
}
