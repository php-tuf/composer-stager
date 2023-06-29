<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::exitEarly
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory
 * @uses \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer
 * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Process\Service\AbstractProcessRunner
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
final class NoSymlinksPointToADirectoryFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointToADirectory
    {
        $container = $this->getContainer();

        // Override the FileSyncer implementation.
        $fileSyncer = $container->getDefinition(FileSyncerInterface::class);
        $fileSyncer->setFactory(null);
        $fileSyncer->setClass(PhpFileSyncer::class);
        $container->setDefinition(FileSyncerInterface::class, $fileSyncer);

        // Compile the container.
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory $sut */
        $sut = $container->get(NoSymlinksPointToADirectory::class);

        return $sut;
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testFulfilledWithValidLink(): void
    {
        $link = PathFactory::create('link.txt', $this->activeDir)->resolved();
        self::ensureParentDirectory($link);
        $target = PathFactory::create('target.txt', $this->activeDir)->resolved();
        self::ensureParentDirectory($target);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $target = PathFactory::create($targetDir . '/target_directory')->resolved();
        $link = PathFactory::create($linkDir . '/directory_link')->resolved();
        mkdir($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains symlinks that point to a directory, which is not supported. The first one is %s.',
            $linkDirName,
            PathFactory::create($linkDir)->resolved(),
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
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::ACTIVE_DIR,
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::STAGING_DIR,
                'linkDirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetDir = './target_directory';
        mkdir(PathFactory::create($targetDir, $this->activeDir)->resolved());
        $links = array_fill_keys($links, $targetDir);
        $exclusions = new PathList(...$exclusions);
        $dirPath = $this->activeDir->resolved();
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
