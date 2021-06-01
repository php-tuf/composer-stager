<?php

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\AbstractRunner;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\AbstractRunner
 * @covers \PhpTuf\ComposerStager\Infrastructure\Process\AbstractRunner::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory|\Prophecy\Prophecy\ObjectProphecy processFactory
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Process\Process $process
 */
class AbstractRunnerTest extends TestCase
{
    private const COMMAND_NAME = 'test';

    public function setUp(): void
    {
        $this->processFactory = $this->prophesize(ProcessFactory::class);
        $this->process = $this->prophesize(Process::class);
    }

    private function createSut()
    {
        $process = $this->process->reveal();
        $this->processFactory
            ->create(Argument::cetera())
            ->willReturn($process);
        $processFactory = $this->processFactory->reveal();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($processFactory) extends AbstractRunner
        {
            protected function executableName(): string
            {
                return 'test';
            }
        };
    }

    /**
     * @covers ::executableName
     * @covers ::run
     *
     * @dataProvider providerRun
     */
    public function testRun($givenCommand, $expectedCommand, $callback): void
    {
        $this->process
            ->mustRun($callback)
            ->shouldBeCalledOnce();
        $this->processFactory
            ->create($expectedCommand)
            ->shouldBeCalled()
            ->willReturn($this->process);

        $sut = $this->createSut();

        $sut->run($givenCommand, $callback);
    }

    public function providerRun(): array
    {
        return [
            [
                'givenCommand' => [],
                'expectedCommand' => [static::COMMAND_NAME],
                'callback' => null,
            ],
            [
                'givenCommand' => ['lorem', 'ipsum'],
                'expectedCommand' => [static::COMMAND_NAME, 'lorem', 'ipsum'],
                'callback' => null,
            ],
            [
                'givenCommand' => [],
                'expectedCommand' => [static::COMMAND_NAME],
                'callback' => static function () {
                },
            ],
        ];
    }

    /**
     * @covers ::run
     */
    public function testRunFailedException(): void
    {
        $this->expectException(ProcessFailedException::class);

        $exception = $this->prophesize(\Symfony\Component\Process\Exception\ProcessFailedException::class);
        $exception = $exception->reveal();
        $this->process
            ->mustRun(Argument::cetera())
            ->willThrow($exception);

        $sut = $this->createSut();

        $sut->run([static::COMMAND_NAME]);
    }
}
