<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist
 *
 * @covers ::__construct
 *
 * @group no_windows
 */
final class NoAbsoluteSymlinksExistFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        return ContainerTestHelper::get(NoAbsoluteSymlinksExist::class);
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerDoesNotContainLinks
     */
    public function testDoesNotContainLinks(array $files): void
    {
        self::createFiles(PathTestHelper::activeDirAbsolute(), $files);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Found no links.');
    }

    public function providerDoesNotContainLinks(): array
    {
        return [
            'Empty directory' => ['files' => []],
            'One file' => ['files' => ['file.txt']],
            'Multiple files' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
            ],
            'Files with directory depth' => [
                'files' => [
                    'one/two.txt',
                    'three/four/five.txt',
                    'six/seven/eight/nine/ten.txt',
                ],
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerLinksExist
     */
    public function testAbsoluteLinksExist(string $dirName, string $basePath, string $link): void
    {
        $linkAbsolute = PathTestHelper::makeAbsolute($link, $basePath);
        $targetRelative = 'target.txt';
        $targetAbsolute = PathTestHelper::makeAbsolute($targetRelative, $basePath);
        $parentDir = dirname($linkAbsolute);
        @mkdir($parentDir, 0777, true);
        FilesystemTestHelper::touch($targetAbsolute);
        // Point at the absolute target path.
        symlink($targetAbsolute, $linkAbsolute);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());
        $statusMessage = $sut->getStatusMessage(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertFalse($isFulfilled, 'Found absolute links.');
        $pattern = sprintf(
            'The %s directory at %s contains absolute links, which is not supported. The first one is %s.',
            $dirName,
            $basePath,
            $linkAbsolute,
        );
        self::assertTranslatableMessage($pattern, $statusMessage, 'Returned correct status message.');
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerLinksExist
     */
    public function testOnlyRelativeLinksExist(string $dirName, string $basePath, string $link): void
    {
        $linkAbsolute = PathTestHelper::makeAbsolute($link, $basePath);
        $targetRelative = 'target.txt';
        $targetAbsolute = PathTestHelper::makeAbsolute($targetRelative, $basePath);
        $parentDirAbsolute = dirname($linkAbsolute);
        @mkdir($parentDirAbsolute, 0777, true);
        FilesystemTestHelper::touch($targetAbsolute);
        chdir($parentDirAbsolute);
        // Point at the relative target path.
        symlink($targetRelative, $linkAbsolute);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Ignored relative links.');
    }

    public function providerLinksExist(): array
    {
        return [
            'Active directory: root' => [
                'dirName' => 'active',
                'basePath' => PathTestHelper::activeDirAbsolute(),
                'link' => 'symlink.txt',
            ],
            'Active directory: subdir' => [
                'dirName' => 'active',
                'basePath' => PathTestHelper::activeDirAbsolute(),
                'link' => 'one/symlink.txt',
            ],
            'Active directory: subdir with depth' => [
                'dirName' => 'active',
                'basePath' => PathTestHelper::activeDirAbsolute(),
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
            'Staging directory: root' => [
                'dirName' => 'staging',
                'basePath' => PathTestHelper::stagingDirAbsolute(),
                'link' => 'symlink.txt',
            ],
            'Staging directory: subdir' => [
                'dirName' => 'staging',
                'basePath' => PathTestHelper::stagingDirAbsolute(),
                'link' => 'one/symlink.txt',
            ],
            'Staging directory: subdir with depth' => [
                'dirName' => 'staging',
                'basePath' => PathTestHelper::stagingDirAbsolute(),
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
        ];
    }

    /** @covers ::assertIsSupportedFile */
    public function testWithHardLink(): void
    {
        $link = PathTestHelper::makeAbsolute('link.txt', PathTestHelper::activeDirAbsolute());
        $target = PathTestHelper::makeAbsolute('target.txt', PathTestHelper::activeDirAbsolute());
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        FilesystemTestHelper::touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Ignored hard link.');
    }

    /**
     * @covers ::assertIsSupportedFile
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = PathTestHelper::activeDirAbsolute();
        self::createFile($dirPath, $targetFile);
        FilesystemTestHelper::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
