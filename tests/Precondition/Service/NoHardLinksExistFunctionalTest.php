<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Symfony\Component\Filesystem\Path;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist
 *
 * @covers ::__construct
 */
final class NoHardLinksExistFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoHardLinksExist
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist $sut */
        $sut = $container->get(NoHardLinksExist::class);

        return $sut;
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::assertIsSupportedFile
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::assertIsSupportedFile
     */
    public function testFulfilledWithSymlink(): void
    {
        $target = Path::makeAbsolute('target.txt', PathHelper::activeDirAbsolute());
        $link = Path::makeAbsolute('link.txt', PathHelper::activeDirAbsolute());
        FilesystemHelper::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Allowed symlink.');
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $directory, string $dirName): void
    {
        $target = PathHelper::makeAbsolute('target.txt', $directory);
        $link = PathHelper::makeAbsolute('link.txt', $directory);
        FilesystemHelper::touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains hard links, which is not supported. The first one is %s.',
            $dirName,
            $directory,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'directory' => PathHelper::activeDirAbsolute(),
                'dirName' => 'active',
            ],
            'In staging directory' => [
                'directory' => PathHelper::stagingDirAbsolute(),
                'dirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsFulfilled
     *
     * @dataProvider providerFulfilledDirectoryDoesNotExist
     */
    public function testFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';

        // The target file is effectively a link just as much as the source, because
        // it has an nlink value of greater than one. So it must be excluded, too.
        $exclusions[] = $targetFile;

        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = PathHelper::activeDirAbsolute();
        self::createFile($dirPath, $targetFile);
        self::createHardlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
