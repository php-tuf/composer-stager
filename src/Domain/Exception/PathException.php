<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException;

/** This exception is the parent for more specific exceptions related to paths. */
class PathException extends RuntimeException implements ExceptionInterface
{
}
