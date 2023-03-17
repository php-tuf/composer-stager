<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Host;

use PhpTuf\ComposerStager\Domain\Service\Host\HostInterface;

final class Host implements HostInterface
{
    public function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}
