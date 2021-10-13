<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 */
class EndToEndFunctionalTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        // For unknown reasons, any attempt to remove the test environment here
        // causes the following confounding test error:
        // > TypeError : chdir() expects parameter 1 to be a valid path, bool given
        // There appears to be a perverse interdependency between this
        // class and the FileSyncer classes since deleting the latter causes
        // test environment removal here to work as expected.
        // self::removeTestEnvironment();
    }

    protected static function runFrontScript(array $args, string $cwd = __DIR__): Process
    {
        // Override default directory paths to be adjacent rather than nested
        // for easier comparison of contents. Set the CWD to the test environment.
        return parent::runFrontScript(array_merge([
            '--active-dir=' . self::ACTIVE_DIR,
            '--staging-dir=' . self::STAGING_DIR,
        ], $args), self::TEST_ENV_WORKING_DIR);
    }

    public function testBegin(): void
    {
        self::initializeComposerJson();

        $process = self::runFrontScript(['begin']);

        self::assertSame('', $process->getErrorOutput());
        self::assertSame(0, $process->getExitCode());
        self::assertActiveAndStagingDirectoriesSame();
    }

    private static function initializeComposerJson(): void
    {
        $process = new Process([
            'composer',
            '--working-dir=' . TestCase::ACTIVE_DIR,
            'init',
            '--name=lorem/ipsum',
            '--no-interaction',
        ]);
        $process->mustRun();
    }

    /**
     * @depends testBegin
     */
    public function testStage(): void
    {
        // A "composer config" command is a good one to stage to avoid "testing
        // the Internet" since it makes no HTTP requests.
        $newName = 'three/four';
        self::runFrontScript([
            'stage',
            '--',
            'config',
            'name',
            $newName,
        ]);
        $process = self::runFrontScript([
            'stage',
            '--',
            'config',
            'name',
        ]);
        $actualName = $process->getOutput();

        self::assertStringStartsWith($newName, $actualName, 'Composer commands succeeded.');
        self::assertSame('', $process->getErrorOutput());
        self::assertSame(0, $process->getExitCode());
        self::assertActiveAndStagingDirectoriesNotSame();
    }

    /**
     * @depends testStage
     */
    public function testCommit(): void
    {
        $process = self::runFrontScript(['commit', '--no-interaction']);

        self::assertSame('', $process->getErrorOutput());
        self::assertSame(0, $process->getExitCode());
        self::assertActiveAndStagingDirectoriesSame();
    }

    /**
     * @depends testCommit
     */
    public function testClean(): void
    {
        $process = self::runFrontScript(['clean', '--no-interaction']);

        self::assertSame('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
        self::assertStagingDirectoryDoesNotExist();
    }
}
