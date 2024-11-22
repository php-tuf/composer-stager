<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NoHardLinksExist::class)]
final class NoHardLinksExistUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'No hard links exist';
    protected const DESCRIPTION = 'The codebase cannot contain hard links.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no hard links in the codebase.';

    protected function createSut(): NoHardLinksExist
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $pathListFactory = self::createPathListFactory();
        $translatableFactory = self::createTranslatableFactory();

        return new NoHardLinksExist($environment, $fileFinder, $filesystem, $pathFactory, $pathListFactory, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    public function testUnfulfilled(): void
    {
        // @todo Implement once the corresponding functionality is added.
        $this->expectNotToPerformAssertions();
    }
}
