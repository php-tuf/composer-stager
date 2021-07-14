<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function runFrontScript(string $commandString): array
    {
        $command = implode(' ', [
            'bin' => 'php',
            'scriptPath' => realpath(__DIR__ . '/../../bin/composer-stage'),
            'commandString' => $commandString,
        ]);

        return self::exec($command);
    }

    protected static function exec(string $command): array
    {
        // These ridiculous proc_open() acrobatics are necessary to prevent tests
        // from printing output, because it is impossible to suppress stderr
        // from exec() and other simpler functions, even with output buffering.
        // @see https://stackoverflow.com/questions/33171386/capture-supress-all-output-from-php-exec-including-stderr
        // @see https://www.php.net/manual/function.proc-open.php
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Could not create a valid process.');
        }

        $stdout = stream_get_contents($pipes[1]);

        proc_close($process);

        return explode(PHP_EOL, $stdout);
    }
}
