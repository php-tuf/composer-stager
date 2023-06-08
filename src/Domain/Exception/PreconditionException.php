<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use RuntimeException;
use Throwable;

/**
 * This exception is thrown when a domain operation has an unfulfilled precondition.
 *
 * @package Exception
 *
 * @api
 */
class PreconditionException extends RuntimeException implements ExceptionInterface
{
    use TranslatableExceptionTrait {
        TranslatableExceptionTrait::__construct as __traitConstruct;
    }

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        private readonly PreconditionInterface $precondition,
        TranslatableInterface $translatableMessage,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->__traitConstruct($translatableMessage, $code, $previous);
    }

    public function getPrecondition(): PreconditionInterface
    {
        return $this->precondition;
    }
}
