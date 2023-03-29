<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use LogicException as PHPLogicException;

/**
 * This exception represents an error in the program logic and should lead to a fix in your code.
 *
 * @package Exception
 *
 * @api
 */
class LogicException extends PHPLogicException implements ExceptionInterface
{
}
