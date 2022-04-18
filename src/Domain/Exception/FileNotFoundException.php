<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use Throwable;

/** This exception is thrown if a file cannot be found. */
class FileNotFoundException extends PathException
{
    public function __construct(
        string $path,
        string $message = 'No such file: "%s"',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
