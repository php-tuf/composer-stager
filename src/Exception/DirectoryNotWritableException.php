<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class DirectoryNotWritableException extends PathException
{
    public function __construct(
        string $path,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if (!$message) {
            $message = sprintf('Directory not writable: "%s"', $path);
        }
        parent::__construct($path, $message, $code, $previous);
    }
}
