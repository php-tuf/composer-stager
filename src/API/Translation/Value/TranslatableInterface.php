<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use Stringable;

/**
 * Handles a translatable message.
 *
 * This interface is modeled after the Symfony Translation component. However,
 * there is no guarantee of functional equivalence. Do not depend on undocumented
 * behavior.
 *
 * @package Translation
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface TranslatableInterface extends Stringable
{
    /** Translates the message. */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string;

    /** Returns the bare message as-given, untranslated, without placeholder substitution. */
    public function __toString(): string;
}
