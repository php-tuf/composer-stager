<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

abstract class FileSyncerFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        FilesystemTestHelper::createDirectories([
            PathTestHelper::sourceDirAbsolute(),
            PathTestHelper::destinationDirAbsolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    final protected function createSut(): FileSyncerInterface
    {
        return ContainerTestHelper::get($this->fileSyncerClass());
    }

    abstract protected function fileSyncerClass(): string;

    /**
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::__construct
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceAndDestinationAreDifferent
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceExists
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::isDescendant
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::sync
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::copySourceFilesToDestination
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::deleteExtraneousFilesFromDestination
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::doSync
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer::isDirEmpty
     * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer::doSync
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $sourceDirRelative,
        string $destinationDirRelative,
        array $givenFiles,
        array $expectedFiles,
    ): void {
        $sourceDirPath = PathTestHelper::createPath($sourceDirRelative);
        $destinationDirPath = PathTestHelper::createPath($destinationDirRelative);
        self::createFiles(PathTestHelper::testFreshFixturesDirAbsolute(), $givenFiles);
        $sut = $this->createSut();

        $sut->sync($sourceDirPath, $destinationDirPath);

        self::assertDirectoryListing(PathTestHelper::testFreshFixturesDirAbsolute(), $expectedFiles);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Source and destination both empty' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'destination-dir',
                'sourceFiles' => [],
                'destinationFiles' => [],
                'expected' => [],
            ],
            'Files in source, destination empty' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'destination-dir',
                'givenFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                ],
                'expectedFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                    'destination-dir/one.txt',
                    'destination-dir/two/three.txt',
                ],
            ],
            'Source empty, files in destination' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'destination-dir',
                'givenFiles' => [
                    'destination-dir/one.txt',
                    'destination-dir/two/three.txt',
                ],
                'expectedFiles' => [],
            ],
            'Completely different files in source and destination' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'destination-dir',
                'givenFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                ],
                'expectedFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                    'destination-dir/one.txt',
                    'destination-dir/two/three.txt',
                ],
                'expected' => ['one.txt', 'two/three.txt'],
            ],
            'Some overlap in files in source and destination' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'destination-dir',
                'givenFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                    'destination-dir/two/three.txt',
                    'destination-dir/four/five.txt',
                ],
                'expectedFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                    'destination-dir/one.txt',
                    'destination-dir/two/three.txt',
                ],
            ],
            'Nested: destination inside source' => [
                'sourceDir' => 'source-dir',
                'destinationDir' => 'source-dir/destination-dir',
                'givenFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                ],
                'expectedFiles' => [
                    'source-dir/one.txt',
                    'source-dir/two/three.txt',
                    'source-dir/destination-dir/one.txt',
                    'source-dir/destination-dir/two/three.txt',
                ],
            ],
            'Nested: source inside destination' => [
                'sourceDir' => 'destination-dir/source-dir',
                'destinationDir' => 'destination-dir',
                'givenFiles' => [
                    'destination-dir/source-dir/one.txt',
                    'destination-dir/source-dir/two/three.txt',
                    'destination-dir/two/three.txt',
                    'destination-dir/four/five/six.txt',
                    'destination-dir/seven/eight/nine/ten.txt',
                    'destination-dir/zz_a_file_sorted_last_in_the_destination_is_important_for_test_coverage.txt',
                ],
                'expectedFiles' => [
                    'destination-dir/source-dir/one.txt',
                    'destination-dir/source-dir/two/three.txt',
                    'destination-dir/one.txt',
                    'destination-dir/two/three.txt',
                ],
            ],
        ];
    }

    /** @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceAndDestinationAreDifferent */
    public function testSyncDirectoriesTheSame(): void
    {
        $samePath = PathTestHelper::arbitraryDirPath();
        $sut = $this->createSut();

        $message = sprintf('The source and destination directories cannot be the same at %s', $samePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $samePath): void {
            $sut->sync($samePath, $samePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::sync
     *
     * @dataProvider providerSyncTimeout
     */
    public function testSyncTimeout(int $timeout): void
    {
        $sut = $this->createSut();

        $sut->sync(PathTestHelper::sourceDirPath(), PathTestHelper::destinationDirPath(), null, null, $timeout);

        self::assertSame((string) $timeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerSyncTimeout(): array
    {
        return [
            'Positive number' => [30],
            'Zero' => [0],
            'Negative number' => [-30],
        ];
    }

    /** @covers ::sync */
    public function testSyncWithDirectorySymlinks(): void
    {
        $link = PathTestHelper::makeAbsolute('link', PathTestHelper::sourceDirAbsolute());
        $target = PathTestHelper::makeAbsolute('directory', PathTestHelper::sourceDirAbsolute());
        FilesystemTestHelper::createDirectories($target);
        $file = PathTestHelper::makeAbsolute('directory/file.txt', PathTestHelper::sourceDirAbsolute());
        FilesystemTestHelper::touch($file);
        symlink($target, $link);
        $sut = $this->createSut();

        $sut->sync(PathTestHelper::sourceDirPath(), PathTestHelper::destinationDirPath());

        self::assertDirectoryListing(PathTestHelper::destinationDirAbsolute(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }

    /** @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceExists */
    public function testSourceDoesNotExist(): void
    {
        $sourcePath = PathTestHelper::nonExistentDirPath();
        $destinationPath = PathTestHelper::arbitraryDirPath();

        $sut = $this->createSut();

        $message = sprintf('The source directory does not exist at %s', $sourcePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $sourcePath, $destinationPath): void {
            $sut->sync($sourcePath, $destinationPath);
        }, LogicException::class, $message);
    }

    /** @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\AbstractFileSyncer::assertSourceExists */
    public function testSourceIsNotADirectory(): void
    {
        $sourcePath = PathTestHelper::arbitraryFilePath();
        $destinationPath = PathTestHelper::arbitraryDirPath();

        FilesystemTestHelper::touch($sourcePath->absolute());
        $sut = $this->createSut();

        $message = sprintf('The source directory is not actually a directory at %s', $sourcePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $sourcePath, $destinationPath): void {
            $sut->sync($sourcePath, $destinationPath);
        }, LogicException::class, $message);
    }
}
