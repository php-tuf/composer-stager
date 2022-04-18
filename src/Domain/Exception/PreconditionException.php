<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use RuntimeException;
use Throwable;

/**
 * This exception is thrown when a domain operation has an unfulfilled precondition.
 *
 * @see /src/Domain/Service/Precondition/README.md
 */
class PreconditionException extends RuntimeException implements ExceptionInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface */
    private $precondition;

    public function __construct(
        PreconditionInterface $precondition,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->precondition = $precondition;

        parent::__construct($message, $code, $previous);
    }

    public function getPrecondition(): PreconditionInterface
    {
        return $this->precondition;
    }
}
