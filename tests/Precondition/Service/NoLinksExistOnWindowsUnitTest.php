<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Infrastructure\Host\Service\HostInterface;
use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\Host\Service\TestHost;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Finder\Service\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 * @property \PhpTuf\ComposerStager\Infrastructure\Host\Service\HostInterface $host
 * @property \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 */
final class NoLinksExistOnWindowsUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function setUp(): void
    {
        $this->host = $this->createWindowsHost();

        parent::setUp();
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no links in the codebase if on Windows.';
    }

    protected function createSut(): NoLinksExistOnWindows
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new NoLinksExistOnWindows($fileFinder, $filesystem, $this->host, $pathFactory, $translatableFactory, $translator);
    }

    private function createWindowsHost(): HostInterface
    {
        return new class() extends TestHost
        {
            public static function isWindows(): bool
            {
                return true;
            }
        };
    }

    private function createNonWindowsHost(): HostInterface
    {
        return new class() extends TestHost
        {
            public static function isWindows(): bool
            {
                return false;
            }
        };
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled('There are no links in the codebase if on Windows.');
    }

    public function testExitEarlyOnNonWindows(): void
    {
        $this->host = $this->createNonWindowsHost();
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }
}
