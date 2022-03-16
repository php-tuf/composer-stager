<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Exception;

use Throwable;

class DirectoryAlreadyExistsException extends PathException
{
    public function __construct(
        string $path,
        string $message = 'Directory already exists: "%s"',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($path, $message, $code, $previous);
    }
}
