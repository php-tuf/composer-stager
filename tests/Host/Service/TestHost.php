<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Host\Service;

use PhpTuf\ComposerStager\Infrastructure\Host\Service\Host;
use PhpTuf\ComposerStager\Infrastructure\Host\Service\HostInterface;

/**
 * This is for "mocking" static methods, since Prophecy can't. Extend it and override methods as needed.
 *
 * phpcs:disable SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
 */
class TestHost implements HostInterface
{
    public static function isWindows(): bool
    {
        return Host::isWindows();
    }
}
