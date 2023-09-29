<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 *
 * @covers ::__construct
 */
final class FileFinderUnitTest extends TestCase
{
    public function testIsTranslatable(): void
    {
        $pathFactory = $this->prophesize(PathFactoryInterface::class)->reveal();
        $translatableFactory = $this->prophesize(TranslatableFactoryInterface::class)->reveal();

        $sut = new FileFinder($pathFactory, $translatableFactory);

        self::assertTranslatableAware($sut);
    }
}
