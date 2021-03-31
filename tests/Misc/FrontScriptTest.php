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
        $path = __DIR__ . '/../../bin/composer-stage';

        $output = [];
        exec("php {$path} --version", $output);

        self::assertSame('Composer Stager', $output[0]);
    }
}
