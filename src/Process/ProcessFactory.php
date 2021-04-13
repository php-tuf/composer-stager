<?php

namespace PhpTuf\ComposerStager\Process;

use Symfony\Component\Process\Process;

class ProcessFactory
{

    public function create(array $array, ...$args): Process
    {
        return new Process($array, ...$args);
    }
}
