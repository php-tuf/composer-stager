<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory
 *
 * @covers ::__construct
 */
final class FileSyncerFactoryUnitTest extends TestCase
{
    private PhpFileSyncerInterface|ObjectProphecy $phpFileSyncer;
    private RsyncFileSyncerInterface|ObjectProphecy $rsyncFileSyncer;
    private SymfonyExecutableFinder|ObjectProphecy $executableFinder;

    protected function setUp(): void
    {
        $this->executableFinder = $this->prophesize(SymfonyExecutableFinder::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturn(null);
        $this->phpFileSyncer = $this->prophesize(PhpFileSyncerInterface::class);
        $this->rsyncFileSyncer = $this->prophesize(RsyncFileSyncerInterface::class);
    }

    private function createSut(): FileSyncerFactory
    {
        $executableFinder = $this->executableFinder->reveal();
        $phpFileSyncer = $this->phpFileSyncer->reveal();
        $rsyncFileSyncer = $this->rsyncFileSyncer->reveal();

        return new FileSyncerFactory($executableFinder, $phpFileSyncer, $rsyncFileSyncer);
    }

    /**
     * @covers ::create
     *
     * @dataProvider providerCreate
     */
    public function testCreate(string $executable, int $calledTimes, ?string $path, string $instanceOf): void
    {
        $this->executableFinder
            ->find($executable)
            ->shouldBeCalledTimes($calledTimes)
            ->willReturn($path);
        $sut = $this->createSut();

        $fileSyncer = $sut->create();

        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($instanceOf, $fileSyncer, 'Returned correct file syncer.');
    }

    public function providerCreate(): array
    {
        return [
            'rsync found' => [
                'executable' => 'rsync',
                'calledTimes' => 1,
                'path' => '/usr/bin/rsync',
                'instanceOf' => RsyncFileSyncerInterface::class,
            ],
            'rsync not found' => [
                'executable' => 'n/a',
                'calledTimes' => 0,
                'path' => null,
                'instanceOf' => PhpFileSyncerInterface::class,
            ],
        ];
    }
}
