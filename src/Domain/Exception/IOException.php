<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use Exception;

/** This exception represents a device error, such as a failed filesystem operation. */
class IOException extends Exception implements ExceptionInterface
{
}
