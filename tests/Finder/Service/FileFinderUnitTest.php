<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 *
 * @covers ::__construct
 */
final class FileFinderUnitTest extends TestCase
{
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private TranslatableFactoryInterface $translatableFactory;

    protected function setUp(): void
    {
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
        $this->translatableFactory = ContainerTestHelper::get(TranslatableFactory::class);
    }

    private function createSut(): FileFinder
    {
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = $this->translatableFactory;

        return new FileFinder($pathFactory, $translatableFactory);
    }

    public function testIsTranslatable(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableAware($sut);
    }

    /** @covers ::getRecursiveDirectoryIterator */
    public function testGetRecursiveDirectoryIteratorException(): void
    {
        $path = PathTestHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The directory cannot be found or is not a directory at %s.', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->find($path);
        }, IOException::class, $message);
    }
}
