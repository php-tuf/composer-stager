<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private $testEnv;

    protected function setUp(): void
    {
        // Create test environment,
        $this->testEnv = realpath(__DIR__ . '/../../var/test_env/') . md5(mt_rand());
        mkdir($this->testEnv);
    }

    protected function tearDown(): void
    {
        // Remove test environment.
        if (file_exists($this->testEnv)) {
            rmdir($this->testEnv);
        }
    }
}
