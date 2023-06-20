<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Exception;

use Exception;

/**
 * This exception represents a device error, such as a failed filesystem operation.
 *
 * @package Exception
 *
 * @api This class is subject to our backward compatibility promise and may be safely depended upon.
 */
class IOException extends Exception implements ExceptionInterface
{
    use TranslatableExceptionTrait;
}
