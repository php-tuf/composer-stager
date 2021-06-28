<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier
 * @covers \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier::__construct
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
 */
class FileCopierTest extends TestCase
{
    public function setUp(): void
    {
        $this->rsync = $this->prophesize(RsyncRunnerInterface::class);
    }

    private function createSut(): FileCopier
    {
        $rsync = $this->rsync->reveal();
        return new FileCopier($rsync);
    }

    /**
     * @covers ::copy
     *
     * @dataProvider providerCopy
     */
    public function testCopy($from, $to, $exclusions, $command, $callback): void
    {
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $copier = $this->createSut();

        $copier->copy($from, $to, $exclusions, $callback);
    }

    public function providerCopy(): array
    {
        return [
            [
                'from' => 'lorem/ipsum',
                'to' => 'dolor/sit',
                'exclusions' => [],
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
                'exclusions' => [
                    'amet',
                    'consectetur',
                ],
                'command' => [
                    '--recursive',
                    '--links',
                    '--verbose',
                    '--exclude=amet',
                    '--exclude=consectetur',
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
        $copier = $this->createSut();

        $copier->copy('lorem', 'ipsum', []);
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
