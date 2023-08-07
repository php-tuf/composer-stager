<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;

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

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

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
        $basePath = self::activeDirPath();
        $link = PathFactory::create('link.txt', $basePath)->absolute();
        $target = PathFactory::create('target.txt', $basePath)->absolute();
        touch($target);
        self::createSymlinks($basePath->absolute(), $symlinks);
        self::createHardlinks($basePath->absolute(), $hardLinks);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $message = sprintf(
            'The active directory at %s contains links, which is not supported on Windows. The first one is %s.',
            $basePath->absolute(),
            $link,
        );
        self::assertTranslatableException(function () use ($sut): void {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
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
    public function testFulfilledDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
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
        $dirPath = $this->activeDir->absolute();
        self::createFile($dirPath, $targetFile);
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
