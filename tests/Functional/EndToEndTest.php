<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 */
class EndToEndTest extends TestCase
{
    private const TEST_ENV = __DIR__ . '/../../var/phpunit/test-env/EndToEndTest';
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';

    public static function setUpBeforeClass(): void
    {
        self::createTestEnvironment();
        self::prepareActiveDirectory();
    }

    public static function tearDownAfterClass(): void
    {
        // Remove the test environment.
        $filesystem = new Filesystem();
        if ($filesystem->exists(self::TEST_ENV)) {
            $filesystem->remove(self::TEST_ENV);
        }
    }

    protected static function runFrontScript(array $args, string $cwd = __DIR__): Process
    {
        // Override default directory paths to be adjacent rather than nested
        // for easier comparison of contents. Set the CWD to the test environment.
        return parent::runFrontScript(array_merge([
            '--active-dir=' . self::ACTIVE_DIR,
            '--staging-dir=' . self::STAGING_DIR,
        ], $args), self::TEST_ENV);
    }

    private static function createTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        // Create the test environment and cd into it,
        $filesystem->mkdir(self::TEST_ENV);
        chdir(self::TEST_ENV);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir(self::ACTIVE_DIR);
    }

    private static function prepareActiveDirectory(): void
    {
        // Initialize composer.json.
        $process = new Process([
            'composer',
            '--working-dir=' . self::ACTIVE_DIR,
            'init',
            '--name=lorem/ipsum',
            '--no-interaction',
        ]);
        $process->mustRun();

        self::assertActiveComposerJsonExists();
    }

    public function testBegin(): void
    {
        $process = self::runFrontScript(['begin']);

        self::assertSame(0, $process->getExitCode());
        self::assertActiveAndStagingDirectoriesSame();
    }

    /**
     * @depends testBegin
     */
    public function testStage(): void
    {
        // A "composer config" command is a good one to stage to avoid "testing
        // the Internet" since it makes no HTTP requests.
        $newName = 'dolor/sit';
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

    private static function assertActiveComposerJsonExists(): void
    {
        self::assertFileExists(self::ACTIVE_DIR . '/composer.json');
    }

    private static function assertStagingDirectoryDoesNotExist(): void
    {
        self::assertFileDoesNotExist(self::STAGING_DIR, 'Staging directory does not exist.');
    }

    private static function assertActiveAndStagingDirectoriesSame(): void
    {
        self::assertTrue(
            self::activeAndStagingDirectoriesAreSame(),
            'Active and staging directories are the same.'
        );
    }

    private static function assertActiveAndStagingDirectoriesNotSame(): void
    {
        self::assertFalse(
            self::activeAndStagingDirectoriesAreSame(),
            'Active and staging directories are not the same.'
        );
    }

    private static function activeAndStagingDirectoriesAreSame(): bool
    {
        try {
            $process = new Process([
                'diff',
                self::ACTIVE_DIR,
                self::STAGING_DIR,
            ]);
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            return false;
        }
        return true;
    }
}
