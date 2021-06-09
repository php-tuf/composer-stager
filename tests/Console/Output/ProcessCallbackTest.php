<?php

namespace PhpTuf\ComposerStager\Tests\Console\Output;

use PhpTuf\ComposerStager\Console\Output\ProcessCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Output\ProcessCallback
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
        $sut = new ProcessCallback($input, $output);

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
