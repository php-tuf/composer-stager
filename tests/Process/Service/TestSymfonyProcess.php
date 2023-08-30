<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Process\Service;

use Symfony\Component\Process\Process as SymfonyProcess;

final class TestSymfonyProcess extends SymfonyProcess
{
    public function __construct(array $command = [])
    {
        parent::__construct($command);
    }

    public function isSuccessful(): bool
    {
        return true;
    }
}
