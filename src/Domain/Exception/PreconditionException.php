<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
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
    public function __construct(
        private readonly PreconditionInterface $precondition,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getPrecondition(): PreconditionInterface
    {
        return $this->precondition;
    }
}
