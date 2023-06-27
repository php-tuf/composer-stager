<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\API\Path\Value\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\API\Path\Value\PathInterface $stagingDir
 */
final class NoHardLinksExistFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoHardLinksExist
    {
        $container = $this->getContainer();
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

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::assertIsSupportedFile
     */
    public function testFulfilledWithSymlink(): void
    {
        $target = PathFactory::create('target.txt', $this->activeDir)->resolved();
        $link = PathFactory::create('link.txt', $this->activeDir)->resolved();
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

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
        $target = PathFactory::create($directory . '/target.txt')->resolved();
        $link = PathFactory::create($directory . '/link.txt')->resolved();
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains hard links, which is not supported. The first one is %s.',
            $dirName,
            PathFactory::create($directory)->resolved(),
            $link,
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'directory' => self::ACTIVE_DIR,
                'dirName' => 'active',
            ],
            'In staging directory' => [
                'directory' => self::STAGING_DIR,
                'dirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
     * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
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
        $dirPath = $this->activeDir->resolved();
        self::createFile($dirPath, $targetFile);
        self::createHardlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
