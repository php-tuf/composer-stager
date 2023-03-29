<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use InvalidArgumentException as PHPInvalidArgumentException;

/**
 * This exception is thrown when an argument doesn't satisfy validation rules.
 *
 * @package Exception
 *
 * @api
 */
class InvalidArgumentException extends PHPInvalidArgumentException implements ExceptionInterface
{
}
