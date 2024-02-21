<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition */
final class AbstractFileIteratingPreconditionFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        FilesystemTestHelper::createDirectories(PathTestHelper::stagingDirRelative());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): AbstractFileIteratingPrecondition
    {
        // The SUT in this case, being abstract, can't be instantiated directly. Use
        // `NoHardLinksExist`, which is easy to create fixtures for, to exercise by extension.
        return ContainerTestHelper::get(NoHardLinksExist::class);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::exitEarly
     * @covers ::findFiles
     *
     * @dataProvider providerFulfilled
     */
    public function testFulfilled(array $files): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();
        self::createFiles($activeDirPath->absolute(), $files);
        self::createFiles($stagingDirPath->absolute(), $files);
        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDirPath, $stagingDirPath));
    }

    public function providerFulfilled(): array
    {
        return [
            'Files' => [
                [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
            ],
            'No files' => [
                [],
            ],
        ];
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        // Use `NoHardLinksExist` to exercise `AbstractFileIteratingPrecondition` by extensions.
        $sut = ContainerTestHelper::get(NoHardLinksExist::class);

        // Make sure that when scanning the active directory for links it doesn't
        // recurse into the nested staging directory. Do this by putting unsupported
        // files in the staging directory and excluding them.
        $activeDir = PathTestHelper::activeDirPath();
        $stagingDir = PathTestHelper::createPath('staging-dir', PathTestHelper::activeDirAbsolute());
        FilesystemTestHelper::touch(PathTestHelper::makeAbsolute('file.txt', $stagingDir->absolute()));
        FilesystemTestHelper::createHardlink($stagingDir->absolute(), 'link.txt', 'file.txt');

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled, 'Found unsupported links.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testWithStagingDirNestedUnderActiveDir(): void
    {
        // Use `NoHardLinksExist` to exercise `AbstractFileIteratingPrecondition` by extensions.
        $sut = ContainerTestHelper::get(NoHardLinksExist::class);

        // Make sure that when scanning the active directory for links it doesn't
        // recurse into the nested staging directory. Do this by putting unsupported
        // files in the staging directory and excluding them.
        $activeDir = PathTestHelper::activeDirPath();
        $stagingDir = PathTestHelper::createPath('staging-dir', PathTestHelper::activeDirAbsolute());
        FilesystemTestHelper::touch(PathTestHelper::makeAbsolute('file.txt', $stagingDir->absolute()));
        FilesystemTestHelper::createHardlink($stagingDir->absolute(), 'link.txt', 'file.txt');

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir, new PathList('link.txt', 'file.txt'));

        self::assertTrue($isFulfilled, 'Excluded nested staging directory while scanning parent active directory.');
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::findFiles
     *
     * @dataProvider providerWithAbsentDirectory
     */
    public function testWithAbsentDirectory(PathInterface $activeDirPath, PathInterface $stagingDirPath): void
    {
        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDirPath, $stagingDirPath));
    }

    public function providerWithAbsentDirectory(): array
    {
        return [
            'No active directory' => [
                'activeDir' => PathTestHelper::nonExistentDirPath(),
                'stagingDir' => PathTestHelper::stagingDirPath(),
            ],
            'No staging directory' => [
                'activeDir' => PathTestHelper::activeDirPath(),
                'stagingDir' => PathTestHelper::nonExistentDirPath(),
            ],
        ];
    }
}
