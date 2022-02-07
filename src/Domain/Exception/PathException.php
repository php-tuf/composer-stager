<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use RuntimeException;
use Throwable;

class PathException extends RuntimeException implements ExceptionInterface
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
