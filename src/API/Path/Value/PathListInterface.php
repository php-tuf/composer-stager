<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Path\Value;

/**
 * Handles a list of path strings.
 *
 * @package Path
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface PathListInterface
{
    /**
     * Returns all path strings as given, canonicalized but unresolved.
     *
     * In other words, directory separators will be normalized and complex
     * paths will be simplified, but they will not be made absolute.
     *
     * @return array<string>
     */
    public function getAll(): array;

    /**
     * Adds a list of raw path strings.
     *
     * Path strings must be relative, e.g., "example" or "../example" but
     * not "/var/www/example". Nothing needs to actually exist at them.
     */
    public function add(string ...$paths): void;
}
