<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException;
use Throwable;

/** This exception is the parent for more specific exceptions related to paths. */
class PathException extends RuntimeException implements ExceptionInterface
{
    public function __construct(string $path, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = sprintf($message, $path);

        parent::__construct($message, $code, $previous);
    }
}
