<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Console;

use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversNothing This actually covers the front script, obviously, but PHPUnit
 *   currently has no way to indicate coverage of a file as opposed to a class.
 * @see https://github.com/sebastianbergmann/phpunit/issues/3794
 */
class FrontScriptFunctionalTest extends TestCase
{
    /**
     * @covers \PhpTuf\ComposerStager\Console\Application::__construct
     */
    public function testBasicExecution(): void
    {
        $process = self::runFrontScript(['--version']);
        $output = $process->getOutput();

        self::assertStringStartsWith('Composer Stager v', $output);
    }

    public function testCommandList(): void
    {
        self::markTestSkipped('Skip due to an apparent upstream change causing this error: An option with shortcut "s" already exists.');

        $process = self::runFrontScript(['--format=json', 'list']);
        $output = $process->getOutput();

        $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
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
}
