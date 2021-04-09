<?php

namespace PhpTuf\ComposerStager\Exception;

use Throwable;

class PathException extends \Exception
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
        parent::__construct($message, $code, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
