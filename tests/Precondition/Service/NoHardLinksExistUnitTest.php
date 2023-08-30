<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist */
final class NoHardLinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function createSut(): NoHardLinksExist
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoHardLinksExist($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no hard links in the codebase.';
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->doTestFulfilled('There are no hard links in the codebase.');
    }

    /** @covers ::assertIsSupportedFile */
    public function testUnfulfilled(): void
    {
        // @todo Implement once the corresponding functionality is added.
        $this->expectNotToPerformAssertions();
    }
}
