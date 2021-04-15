<?php

// It's overkill mocking a builtin PHP function in this instance, but it's
// valuable to have an example of doing it in the codebase.
namespace PhpTuf\ComposerStager\Filesystem {
    function is_writable(string $filename): bool
    {
        return $filename === 'writable';
    }
}

namespace PhpTuf\ComposerStager\Tests\Filesystem {
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

        /**
         * @covers ::isWritable
         *
         * @dataProvider providerIsWritable
         */
        public function testIsWritable($filename, $expected): void
        {
            $filesystem = new Filesystem();

            $actual = $filesystem->isWritable($filename);

            self::assertSame($expected, $actual);
        }

        public function providerIsWritable(): array
        {
            return [
                ['writable', true],
                ['not_writable', false],
            ];
        }
    }
}
