<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 * @covers ::assertIsSupportedFile
 * @covers ::exitEarly
 */
final class NoLinksExistOnWindowsUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'No links exist on Windows';
    protected const DESCRIPTION = 'The codebase cannot contain links if on Windows.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no links in the codebase if on Windows.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->environment
            ->isWindows()
            ->willReturn(true);
    }

    protected function createSut(): NoLinksExistOnWindows
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        return new NoLinksExistOnWindows($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    /** @covers ::getFulfilledStatusMessage */
    public function testFulfilled(): void
    {
        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE);
    }

    public function testExitEarlyOnNonWindows(): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();

        $this->environment
            ->isWindows()
            ->willReturn(false);
        $this->filesystem
            ->fileExists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
    }
}
