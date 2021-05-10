<?php

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Filesystem;

use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 */
class FilesystemTest extends TestCase
{
    /**
     * @covers ::getcwd
     */
    public function testGetcwd(): void
    {
        $filesystem = new Filesystem();

        self::assertSame(getcwd(), $filesystem->getcwd());
    }
}
