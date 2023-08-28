<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;

final class TestEnvironment implements EnvironmentInterface
{
    public function isWindows(): bool
    {
        return false;
    }

    public function setTimeLimit(int $seconds): bool
    {
        return true;
    }
}
