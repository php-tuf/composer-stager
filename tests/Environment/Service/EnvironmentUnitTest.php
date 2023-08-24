<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Environment\Service;

use PhpTuf\ComposerStager\Internal\Environment\Service\Environment;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Environment\Service\Environment */
final class EnvironmentUnitTest extends TestCase
{
    public function createSut(): Environment
    {
        return new Environment();
    }

    /** @covers ::isWindows */
    public function testIsWindows(): void
    {
        $isWindowsDirectorySeparator = DIRECTORY_SEPARATOR === '\\';
        $sut = $this->createSut();

        self::assertEquals($isWindowsDirectorySeparator, $sut->isWindows());
    }
}
