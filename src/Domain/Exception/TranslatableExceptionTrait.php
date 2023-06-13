<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use Throwable;

/**
 * Provides features for translatable exceptions.
 *
 * @see \PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface
 *
 * @package Exception
 *
 * @api
 */
trait TranslatableExceptionTrait
{
    public function __construct(
        private readonly TranslatableInterface $translatableMessage,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct((string) $translatableMessage, $code, $previous);
    }

    /** @see \PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface::getTranslatableMessage */
    public function getTranslatableMessage(): TranslatableInterface
    {
        return $this->translatableMessage;
    }
}
