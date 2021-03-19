<?php

namespace PhpTuf\ComposerStager\Tests\Misc;

use PHPUnit\Framework\TestCase;

class FrontScriptTest extends TestCase
{
    public function testBasicExecution(): void
    {
        $output = [];
        // This ugly "dirname" construct is for Windows compatibility.
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
        exec($path . 'composer-stage --version', $output);

        self::assertSame('Composer Stager', $output[0]);
    }
}
