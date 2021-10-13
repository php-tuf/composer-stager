<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Console\Output;

use PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback
 * @covers ::__invoke
 * @covers ::__construct
 */
class ProcessCallbackUnitTest extends TestCase
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
        $sut = new ProcessOutputCallback($input, $output);

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
