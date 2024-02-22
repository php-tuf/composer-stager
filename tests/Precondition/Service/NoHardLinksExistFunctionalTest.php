<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
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
        return ContainerTestHelper::get(NoHardLinksExist::class);
    }

    /** @covers ::assertIsSupportedFile */
    public function testFulfilledWithSymlink(): void
    {
        $target = Path::makeAbsolute('target.txt', PathTestHelper::activeDirAbsolute());
        $link = Path::makeAbsolute('link.txt', PathTestHelper::activeDirAbsolute());
        FilesystemTestHelper::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Allowed symlink.');
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $directory, string $dirName): void
    {
        $target = PathTestHelper::makeAbsolute('target.txt', $directory);
        $link = PathTestHelper::makeAbsolute('link.txt', $directory);
        FilesystemTestHelper::touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains hard links, which is not supported. The first one is %s.',
            $dirName,
            $directory,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'directory' => PathTestHelper::activeDirAbsolute(),
                'dirName' => 'active',
            ],
            'In staging directory' => [
                'directory' => PathTestHelper::stagingDirAbsolute(),
                'dirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
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
        $exclusions = PathTestHelper::createPathList(...$exclusions);
        $dirPath = PathTestHelper::activeDirAbsolute();
        self::createFile($dirPath, $targetFile);
        FilesystemTestHelper::createHardlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
