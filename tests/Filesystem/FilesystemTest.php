<?php

namespace PhpTuf\ComposerStager\Tests\Filesystem;

use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Filesystem\Filesystem
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
