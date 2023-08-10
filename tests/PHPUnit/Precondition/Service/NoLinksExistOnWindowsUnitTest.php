<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Host\Service\HostInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\Host\Service\TestHost;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 */
final class NoLinksExistOnWindowsUnitTest extends FileIteratingPreconditionUnitTestCase
{
    private HostInterface $host;

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

        return new NoLinksExistOnWindows($fileFinder, $filesystem, $this->host, $pathFactory, $translatableFactory);
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
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->host = $this->createNonWindowsHost();
        $this->filesystem
            ->exists(Argument::cetera())
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
