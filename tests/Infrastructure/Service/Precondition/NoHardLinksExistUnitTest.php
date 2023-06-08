<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Infrastructure\Service\Translation\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist
 *
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
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
