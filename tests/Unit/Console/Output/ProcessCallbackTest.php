<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Console\Output;

use PhpTuf\ComposerStager\Console\Output\Callback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Output\Callback
 * @covers ::__invoke
 * @covers ::__construct
 */
class ProcessCallbackTest extends TestCase
{
    /**
     * @dataProvider providerInvoke
     */
    public function testInvoke($message, $quiet, $write): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input
            ->getOption('quiet')
            ->willReturn($quiet);
        $input = $input->reveal();
        $output = $this->prophesize(OutputInterface::class);
        $output
            ->write($message)
            ->shouldBeCalledTimes((bool) $write);
        $output = $output->reveal();
        $sut = new Callback($input, $output);

        $sut(Process::OUT, $message);
    }

    public function providerInvoke(): array
    {
        return [
            [
                'message' => 'lorem ipsum',
                'quiet' => false,
                'write' => true,
            ],
            [
                'message' => 'dolor sit',
                'quiet' => true,
                'write' => false,
            ],
        ];
    }
}
