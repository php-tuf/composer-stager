<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException;

class ProcessFailedException extends RuntimeException implements ExceptionInterface
{
}
