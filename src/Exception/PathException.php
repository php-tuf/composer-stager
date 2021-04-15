<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class PathException extends IOException
{
    /**
     * @var string
     */
    private $path;

    public function __construct(
        string $path,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->path = $path;
        $message = sprintf($message, $path);
        parent::__construct($message, $code, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
