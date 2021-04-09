<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class DirectoryNotFoundException extends PathException
{
    public function __construct(
        string $path,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if (!$message) {
            $message = sprintf('No such directory: "%s"', $path);
        }
        parent::__construct($path, $message, $code, $previous);
    }
}
