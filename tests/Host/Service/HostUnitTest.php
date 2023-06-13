<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Host\Service;

use PhpTuf\ComposerStager\Infrastructure\Host\Service\Host;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Host\Service\Host */
final class HostUnitTest extends TestCase
{
    /** @covers ::isWindows */
    public function testWindows(): void
    {
        $isWindowsDirectorySeparator = DIRECTORY_SEPARATOR === '\\';

        self::assertEquals($isWindowsDirectorySeparator, Host::isWindows());
    }
}
