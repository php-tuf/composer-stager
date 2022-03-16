<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgumentException extends PHPInvalidArgumentException implements ExceptionInterface
{
}
