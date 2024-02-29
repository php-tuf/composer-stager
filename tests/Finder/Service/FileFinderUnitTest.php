<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 *
 * @covers ::__construct
 */
final class FileFinderUnitTest extends TestCase
{
    private function createSut(): FileFinder
    {
        $pathFactory = self::createPathFactory();
        $translatableFactory = self::createTranslatableFactory();
        $pathListFactory = self::createPathListFactory();

        return new FileFinder($pathFactory, $pathListFactory, $translatableFactory);
    }

    public function testIsTranslatable(): void
    {
        $sut = $this->createSut();

        self::assertTranslatableAware($sut);
    }

    /** @covers ::getRecursiveDirectoryIterator */
    public function testGetRecursiveDirectoryIteratorException(): void
    {
        $path = self::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The directory cannot be found or is not a directory at %s.', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->find($path);
        }, IOException::class, $message);
    }
}
