<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException;

/** This exception is thrown when a process doesn't complete successfully. */
class ProcessFailedException extends RuntimeException implements ExceptionInterface
{
}
