<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist
 *
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Finder\Service\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 */
final class NoHardLinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoHardLinksExist
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new NoHardLinksExist($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no hard links in the codebase.';
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled('There are no hard links in the codebase.');
    }

    public function testUnfulfilled(): void
    {
        // @todo Implement once the corresponding functionality is added.
        $this->expectNotToPerformAssertions();
    }
}
