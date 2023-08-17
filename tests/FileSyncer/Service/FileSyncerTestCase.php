<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Internal\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

abstract class FileSyncerTestCase extends TestCase
{
    protected EnvironmentInterface|ObjectProphecy $environment;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
    }

    abstract protected function createSut(): FileSyncerInterface;

    /**
     * @covers ::sync
     *
     * @dataProvider providerTimeout
     */
    public function testTimeout(int $timeout): void
    {
        $this->environment->setTimeLimit($timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        // Use the same path for the source and destination in
        // order to fail validation and avoid side effects.
        try {
            $sut->sync(PathHelper::sourceDirPath(), PathHelper::sourceDirPath(), null, null, $timeout);
        } catch (Throwable) {
            // @ignoreException
        }
    }

    public function providerTimeout(): array
    {
        return [
            [-30],
            [-5],
            [0],
            [5],
            [30],
        ];
    }
}
