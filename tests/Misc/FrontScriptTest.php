<?php

namespace PhpTuf\ComposerStager\Tests\Misc;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing This actually covers the front script, obviously, but PHPUnit
 *   currently has no way to indicate coverage of a file as opposed to a class.
 * @see https://github.com/sebastianbergmann/phpunit/issues/3794
 */
class FrontScriptTest extends TestCase
{
    public function testBasicExecution(): void
    {
        $output = $this->runFrontScript('--version');

        self::assertSame('Composer Stager', $output[0]);
    }

    public function testCommandList(): void
    {
        $output = $this->runFrontScript('--format=json list');

        $data = json_decode($output[0], true, 512, JSON_THROW_ON_ERROR);
        $commands = array_map(static function ($value) {
            return $value['name'];
        }, $data['commands']);

        self::assertSame([
            'begin',
            'clean',
            'commit',
            'help',
            'list',
            'stage',
        ], $commands);
    }

    private function runFrontScript(string $commandString): array
    {
        $output = [];

        $command = implode(' ', [
            'bin' => 'php',
            'script_path' => __DIR__ . '/../../bin/composer-stage',
            'command_string' => $commandString,
        ]);

        exec($command, $output);

        return $output;
    }
}
