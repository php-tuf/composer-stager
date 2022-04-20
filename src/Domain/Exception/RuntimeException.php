<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException as PHPRuntimeException;

/** This exception is thrown if an error occurs that can only be found at runtime. */
class RuntimeException extends PHPRuntimeException implements ExceptionInterface
{
}
