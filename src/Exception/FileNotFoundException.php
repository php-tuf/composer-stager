<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class FileNotFoundException extends PathException
{
    public function __construct(
        string $path,
        string $message = 'No such file: "%s"',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
