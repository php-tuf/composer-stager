<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist
 *
 * @covers ::__construct
 * @covers ::findFiles
 *
 * @group no_windows
 */
final class NoAbsoluteSymlinksExistFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoAbsoluteSymlinksExist
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist $sut */
        $sut = $container->get(NoAbsoluteSymlinksExist::class);

        return $sut;
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerDoesNotContainLinks
     */
    public function testDoesNotContainLinks(array $files): void
    {
        self::createFiles(PathHelper::activeDirAbsolute(), $files);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

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
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @dataProvider providerLinksExist
     */
    public function testAbsoluteLinksExist(string $dirName, string $basePath, string $link): void
    {
        $linkAbsolute = PathHelper::makeAbsolute($link, $basePath);
        $targetRelative = 'target.txt';
        $targetAbsolute = PathHelper::makeAbsolute($targetRelative, $basePath);
        $parentDir = dirname($linkAbsolute);
        @mkdir($parentDir, 0777, true);
        touch($targetAbsolute);
        // Point at the absolute target path.
        symlink($targetAbsolute, $linkAbsolute);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        $statusMessage = $sut->getStatusMessage(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

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
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @dataProvider providerLinksExist
     */
    public function testOnlyRelativeLinksExist(string $dirName, string $basePath, string $link): void
    {
        $linkAbsolute = PathHelper::makeAbsolute($link, $basePath);
        $targetRelative = 'target.txt';
        $targetAbsolute = PathHelper::makeAbsolute($targetRelative, $basePath);
        $parentDirAbsolute = dirname($linkAbsolute);
        @mkdir($parentDirAbsolute, 0777, true);
        touch($targetAbsolute);
        chdir($parentDirAbsolute);
        // Point at the relative target path.
        symlink($targetRelative, $linkAbsolute);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Ignored relative links.');
    }

    public function providerLinksExist(): array
    {
        return [
            'Active directory: root' => [
                'dirName' => 'active',
                'basePath' => PathHelper::activeDirAbsolute(),
                'link' => 'symlink.txt',
            ],
            'Active directory: subdir' => [
                'dirName' => 'active',
                'basePath' => PathHelper::activeDirAbsolute(),
                'link' => 'one/symlink.txt',
            ],
            'Active directory: subdir with depth' => [
                'dirName' => 'active',
                'basePath' => PathHelper::activeDirAbsolute(),
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
            'Staging directory: root' => [
                'dirName' => 'staging',
                'basePath' => PathHelper::stagingDirAbsolute(),
                'link' => 'symlink.txt',
            ],
            'Staging directory: subdir' => [
                'dirName' => 'staging',
                'basePath' => PathHelper::stagingDirAbsolute(),
                'link' => 'one/symlink.txt',
            ],
            'Staging directory: subdir with depth' => [
                'dirName' => 'staging',
                'basePath' => PathHelper::stagingDirAbsolute(),
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
        ];
    }

    /**
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
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testWithHardLink(): void
    {
        $link = PathHelper::makeAbsolute('link.txt', PathHelper::activeDirAbsolute());
        $target = PathHelper::makeAbsolute('target.txt', PathHelper::activeDirAbsolute());
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
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
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = PathHelper::activeDirAbsolute();
        self::createFile($dirPath, $targetFile);
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
