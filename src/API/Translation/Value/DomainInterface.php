<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Translation\Value;

/**
 * Provides domain values for the translation system.
 *
 * @package Translation
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface DomainInterface
{
    public const DEFAULT = 'messages';
    public const EXCEPTIONS = 'exceptions';
}
