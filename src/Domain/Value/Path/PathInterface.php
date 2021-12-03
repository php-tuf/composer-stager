<?php

namespace PhpTuf\ComposerStager\Domain\Value\Path;

use Stringable;

/**
 * Takes a filesystem path string and provides it as a fully resolved, absolute path.
 *
 * The path string may be absolute or relative to the current working directory (CWD),
 * e.g., "/var/www/example" or "example". Nothing needs to actually exist at the path.
 */
interface PathInterface extends Stringable
{
    /**
     * Gets the fully resolved, absolute path.
     */
    public function getAbsolute(): string;
}
