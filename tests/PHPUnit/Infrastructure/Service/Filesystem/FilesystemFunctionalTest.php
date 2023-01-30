<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \Symfony\Component\Filesystem\Filesystem
 */
final class FilesystemFunctionalTest extends TestCase
{
    private const SOURCE_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'source';
    private const DESTINATION_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'destination';

    protected function setUp(): void
    {
        $filesystem = new SymfonyFilesystem();

        $filesystem->mkdir(self::SOURCE_DIR);
        $filesystem->mkdir(self::DESTINATION_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): Filesystem
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem $filesystem */
        $filesystem = $container->get(Filesystem::class);

        return $filesystem;
    }

    /** @covers ::copy */
    public function testCopy(): void
    {
        $filename = 'file.txt';
        self::createFile(self::SOURCE_DIR, $filename);

        $filesystem = $this->createSut();

        $source = PathFactory::create(self::SOURCE_DIR . DIRECTORY_SEPARATOR . $filename);
        $destination = PathFactory::create(self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $filename);

        // Copy an individual file.
        $filesystem->copy($source, $destination);

        self::assertDirectoryListing(self::DESTINATION_DIR, [$filename]);
    }

    /**
     * @covers ::isLink
     *
     * @dataProvider providerIsLink
     */
    public function testIsLink(array $files, array $links, bool $expected): void
    {
        self::createFiles(self::SOURCE_DIR, $files);
        self::createSymlinks(self::SOURCE_DIR, $links);
        $path = self::SOURCE_DIR . DIRECTORY_SEPARATOR . 'link.txt';
        $sut = $this->createSut();

        $actual = $sut->isLink(PathFactory::create($path));

        self::assertSame($expected, $actual, 'Correctly determined whether path was a link.');
    }

    public function providerIsLink(): array
    {
        return [
            'File is a link' => [
                'files' => ['target.txt'],
                'links' => ['link.txt' => 'target.txt'],
                'expected' => true,
            ],
            'File is not a link' => [
                'files' => ['file.txt'],
                'links' => [],
                'expected' => false,
            ],
            'No file there' => [
                'files' => [],
                'links' => [],
                'expected' => false,
            ],
        ];
    }

    /** @covers ::readLink */
    public function testReadlink(): void
    {
        self::createFile(self::SOURCE_DIR, 'target.txt');
        self::createSymlink(self::SOURCE_DIR, 'link.txt', 'target.txt');
        $link = PathFactory::create(self::SOURCE_DIR . '/link.txt');
        $target = PathFactory::create(self::SOURCE_DIR . '/target.txt');
        $sut = $this->createSut();

        $actual = $sut->readLink($link);

        self::assertSame($target->resolve(), $actual->resolve(), 'Correctly read link.');
    }

    /** @covers ::readLink */
    public function testReadlinkFailure(): void
    {
        $this->expectException(IOException::class);

        $sut = $this->createSut();

        $path = self::SOURCE_DIR . DIRECTORY_SEPARATOR . 'non-existent_file.txt';
        $sut->readLink(PathFactory::create($path));
    }

    /**
     * Our filesystem service currently depends on the Symfony Filesystem component
     * and currently delegates its copy() method directly to it. Therefore, it is
     * precisely equivalent to its implementation. Symfony's copy() documentation
     * does not specify whether it supports directories as well as files. This
     * test is to discover whether it does. (At this time it does not.)
     *
     * @coversNothing
     */
    public function testSymfonyCopyDirectory(): void
    {
        $this->expectException(SymfonyIOException::class);

        $dirname = 'directory';
        $files = [
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file2',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file3',
        ];
        self::createFiles(self::SOURCE_DIR, $files);
        $symfonyFilesystem = new SymfonyFilesystem();

        self::assertDirectoryListing(self::SOURCE_DIR, $files);

        // Single file copy: this should work.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
        );

        // Directory copy: this should fail.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname,
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname,
        );
    }
}
