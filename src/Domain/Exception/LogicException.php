<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use LogicException as PHPLogicException;

class LogicException extends PHPLogicException implements ExceptionInterface
{
}
