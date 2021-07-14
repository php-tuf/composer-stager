<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversNothing
 */
class EndToEndTest extends TestCase
{
    private const ACTIVE_DIR = 'active-dir';
    private const STAGING_DIR = 'staging-dir';

    private $testEnv;

    protected function setUp(): void
    {
        $filesystem = new Filesystem();

        // Create the test environment and cd into it,
        $this->testEnv = __DIR__ . '/../../var/phpunit/test-env/' . md5(mt_rand());
        $filesystem->mkdir($this->testEnv);
        chdir($this->testEnv);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir(self::ACTIVE_DIR);
    }

    protected function tearDown(): void
    {
        // Remove the test environment.
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->testEnv)) {
            $filesystem->remove($this->testEnv);
        }
    }

    protected function runFrontScript(string $commandString): array
    {
        // Override default directory paths to be adjacent rather than nested
        // for easier comparison of contents.
        $commandString = sprintf(
            '--active-dir=%s --staging-dir=%s %s',
            self::ACTIVE_DIR,
            self::STAGING_DIR,
            $commandString
        );
        return parent::runFrontScript($commandString);
    }

    public function testEndToEnd(): void
    {
        $this->assertTestPreconditions();
        $this->prepareActiveDirectory();

        $this->testBegin();
        $this->testStage();
        $this->testCommit();
        $this->testClean();
    }

    private function assertTestPreconditions(): void
    {
        self::assertActiveDirectoryExists();
        self::assertActiveDirectoryIsEmpty();
        self::assertStagingDirectoryDoesNotExist();
    }

    private function prepareActiveDirectory(): void
    {
        // Initialize composer.json.
        self::exec(sprintf(
            'composer --working-dir=%s init --name="lorem/ipsum" --no-interaction',
            self::ACTIVE_DIR
        ));

        self::assertActiveComposerJsonExists();
    }

    private function testBegin(): void
    {
        $this->runFrontScript('begin');

        self::assertActiveAndStagingDirectoriesSame();
    }

    private function testStage(): void
    {
        // A "composer config" command is a good one to stage to avoid "testing
        // the Internet" since it makes no HTTP requests.
        $newName = 'dolor/sit';
        $this->runFrontScript("stage -- config name {$newName}");
        $actualName = $this->runFrontScript('stage -- config name')[0];

        self::assertEquals($newName, $actualName, 'Composer commands succeeded.');
        self::assertActiveAndStagingDirectoriesNotSame();
    }

    private function testCommit(): void
    {
        $this->runFrontScript('commit --no-interaction');

        self::assertActiveAndStagingDirectoriesSame();
    }

    private function testClean(): void
    {
        $this->runFrontScript('clean --no-interaction');

        self::assertStagingDirectoryDoesNotExist();
    }

    private static function assertActiveComposerJsonExists(): void
    {
        self::assertFileExists(self::ACTIVE_DIR . '/composer.json');
    }

    private static function assertActiveDirectoryExists(): void
    {
        self::assertFileExists(self::ACTIVE_DIR, 'Active directory exists.');
    }

    private static function assertActiveDirectoryIsEmpty(): void
    {
        $contents = scandir(self::ACTIVE_DIR);

        // Remove items for the current and parent directories.
        $contents = array_diff($contents, ['.', '..']);

        // Reindex the return array for clearer diffs.
        self::assertEmpty(array_values($contents), 'Active directory is empty.');
    }

    private static function assertStagingDirectoryDoesNotExist(): void
    {
        self::assertFileDoesNotExist(self::STAGING_DIR, 'Staging directory does not exist.');
    }

    private static function assertActiveAndStagingDirectoriesSame(): void
    {
        self::assertSame(
            [],
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are the same.'
        );
    }

    private static function assertActiveAndStagingDirectoriesNotSame(): void
    {
        self::assertNotSame(
            [],
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are not the same.'
        );
    }

    private static function getActiveAndStagingDirectoriesDiff(): array
    {
        $diff = self::exec(sprintf(
            'diff %s %s',
            self::ACTIVE_DIR,
            self::STAGING_DIR
        ));
        return array_filter($diff);
    }
}
