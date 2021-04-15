<?php

namespace PhpTuf\ComposerStager\Process;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    /**
     * @param string[] $array
     * @param mixed ...$args
     *
     * @return \Symfony\Component\Process\Process<\Generator>
     */
    public function create(array $array, ...$args): Process
    {
        return new Process($array, ...$args);
    }
}
