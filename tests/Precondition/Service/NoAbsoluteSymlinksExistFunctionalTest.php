<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;

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
        $container = $this->getContainer();
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
        self::createFiles(self::ACTIVE_DIR_RELATIVE, $files);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

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
    public function testAbsoluteLinksExist(string $dirName, PathInterface $dirPath, string $link): void
    {
        $link = PathFactory::create($link, $dirPath);
        $target = PathFactory::create('target.txt', $dirPath);
        $parentDir = dirname($link->resolved());
        @mkdir($parentDir, 0777, true);
        touch($target->resolved());
        // Point at the resolved target, i.e., its absolute path.
        symlink($target->resolved(), $link->resolved());
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());
        $statusMessage = $sut->getStatusMessage(self::activeDirPath(), self::stagingDirPath());

        self::assertFalse($isFulfilled, 'Found absolute links.');
        $pattern = sprintf(
            'The %s directory at %s contains absolute links, which is not supported. The first one is %s.',
            $dirName,
            $dirPath->resolved(),
            $link->resolved(),
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
    public function testOnlyRelativeLinksExist(string $dirName, PathInterface $dirPath, string $link): void
    {
        $link = PathFactory::create($link, $dirPath);
        $target = PathFactory::create('target.txt', $dirPath);
        $parentDir = dirname($link->resolved());
        @mkdir($parentDir, 0777, true);
        touch($target->resolved());
        chdir($parentDir);
        // Point at the raw target, i.e., its relative path.
        symlink($target->raw(), $link->resolved());
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertTrue($isFulfilled, 'Ignored relative links.');
    }

    public function providerLinksExist(): array
    {
        return [
            'Active directory: root' => [
                'dirName' => 'active',
                'dirPath' => self::activeDirPath(),
                'link' => 'symlink.txt',
            ],
            'Active directory: subdir' => [
                'dirName' => 'active',
                'dirPath' => self::activeDirPath(),
                'link' => 'one/symlink.txt',
            ],
            'Active directory: subdir with depth' => [
                'dirName' => 'active',
                'dirPath' => self::activeDirPath(),
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
            'Staging directory: root' => [
                'dirName' => 'staging',
                'dirPath' => self::stagingDirPath(),
                'link' => 'symlink.txt',
            ],
            'Staging directory: subdir' => [
                'dirName' => 'staging',
                'dirPath' => self::stagingDirPath(),
                'link' => 'one/symlink.txt',
            ],
            'Staging directory: subdir with depth' => [
                'dirName' => 'staging',
                'dirPath' => self::stagingDirPath(),
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
    public function testFulfilledDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testWithHardLink(): void
    {
        $link = PathFactory::create('link.txt', self::activeDirPath())->resolved();
        $target = PathFactory::create('target.txt', self::activeDirPath())->resolved();
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

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
        $dirPath = self::activeDirPath()->resolved();
        self::createFile($dirPath, $targetFile);
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
