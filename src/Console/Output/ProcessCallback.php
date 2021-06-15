<?php

namespace PhpTuf\ComposerStager\Console\Output;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class ProcessCallback
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
    }

    /**
     * @param string $type
     *   The output type. Possible values are
     *   \PhpTuf\ComposerStager\Domain\OutputType::ERR and
     *   \PhpTuf\ComposerStager\Domain\OutputType::OUT.
     * @param string $buffer
     *   The message to output.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function __invoke(string $type, string $buffer): void
    {
        if ($this->input->getOption('quiet') === true) {
            return;
        }

        // Write process output as it comes.
        $this->output->write($buffer);
    }
}
