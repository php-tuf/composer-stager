<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process\FileCopier;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier
 * @covers ::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
 */
class RsyncFileCopierTest extends TestCase
{
    public function setUp(): void
    {
        $this->rsync = $this->prophesize(RsyncRunnerInterface::class);
    }

    protected function createSut(): RsyncFileCopier
    {
        $rsync = $this->rsync->reveal();
        return new RsyncFileCopier($rsync);
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopy
     */
    public function testCopy($from, $to, $command, $callback): void
    {
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->copy($from, $to, [], $callback);
    }

    public function providerCopy(): array
    {
        return [
            [
                'from' => 'lorem/ipsum',
                'to' => 'dolor/sit',
                'command' => [
                    '--recursive',
                    '--links',
                    '--verbose',
                    'lorem/ipsum' . DIRECTORY_SEPARATOR,
                    'dolor/sit',
                ],
                'callback' => null,
            ],
            [
                'from' => 'ipsum/lorem' . DIRECTORY_SEPARATOR,
                'to' => 'sit/dolor',
                'command' => [
                    '--recursive',
                    '--links',
                    '--verbose',
                    'ipsum/lorem' . DIRECTORY_SEPARATOR,
                    'sit/dolor',
                ],
                'callback' => new TestProcessOutputCallback(),
            ],
        ];
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopyFailure
     */
    public function testCopyFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->copy('lorem', 'ipsum', []);
    }

    public function providerCopyFailure(): array
    {
        return [
            [IOException::class],
            [LogicException::class],
            [ProcessFailedException::class],
        ];
    }
}
