<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Path\Value;

/**
 * @package Path
 *
 * @api This class is subject to our backward compatibility promise and may be safely depended upon.
 */
final class PathList implements PathListInterface
{
    /** @var array<string> */
    private array $paths = [];

    public function __construct(string ...$paths)
    {
        $this->paths = $paths;
    }

    /** @return array<string> */
    public function getAll(): array
    {
        return $this->paths;
    }

    public function add(string ...$paths): void
    {
        $this->paths = array_merge($this->paths, $paths);
    }
}
