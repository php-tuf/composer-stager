<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use Symfony\Component\Process\Process;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static function runFrontScript(array $args, string $cwd = __DIR__): Process
    {
        $command = array_merge([
            'bin' => 'php',
            'scriptPath' => realpath(__DIR__ . '/../../bin/composer-stage'),
        ], $args);
        $process = new Process($command, $cwd);
        $process->mustRun();
        return $process;
    }
}
