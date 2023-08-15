<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 */
final class NoLinksExistOnWindowsFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoLinksExistOnWindows
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows $sut */
        $sut = $container->get(NoLinksExistOnWindows::class);

        return $sut;
    }

    /**
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @dataProvider providerUnfulfilled
     *
     * @group windows_only
     */
    public function testUnfulfilled(array $symlinks, array $hardLinks): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $basePathAbsolute = PathHelper::activeDirAbsolute();
        $link = PathHelper::makeAbsolute('link.txt', $basePathAbsolute);
        $target = PathHelper::makeAbsolute('target.txt', $basePathAbsolute);
        FilesystemHelper::touch($target);
        FilesystemHelper::createSymlinks($basePathAbsolute, $symlinks);
        FilesystemHelper::createHardlinks($basePathAbsolute, $hardLinks);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $message = sprintf(
            'The active directory at %s contains links, which is not supported on Windows. The first one is %s.',
            $basePathAbsolute,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'Contains symlink' => [
                'symlinks' => ['link.txt' => 'target.txt'],
                'hardLinks' => [],
            ],
            'Contains hard link' => [
                'symlinks' => [],
                'hardLinks' => ['link.txt' => 'target.txt'],
            ],
        ];
    }

    /**
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @dataProvider providerFulfilledDirectoryDoesNotExist
     */
    public function testFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::exitEarly
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     *
     * @group windows_only
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $basePath = PathHelper::activeDirAbsolute();
        self::createFile($basePath, $targetFile);
        FilesystemHelper::createSymlinks($basePath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
