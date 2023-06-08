<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use Throwable;

/**
 * An interface that all concrete exceptions must implement.
 *
 * @package Exception
 *
 * @api
 */
interface ExceptionInterface extends Throwable
{
    /**
     * Gets the translatable form of the message with original metadata intact.
     *
     * @see \PhpTuf\ComposerStager\Domain\Exception\TranslatableExceptionTrait
     */
    public function getTranslatableMessage(): TranslatableInterface;
}
