<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Compatability;

use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 *
 * @group slow
 */
final class CompatabilityFunctionalTest extends TestCase
{
    // @see https://github.com/php-tuf/composer-stager/wiki/Library-compatibility-policy#drupal
    private const SUPPORTED_DRUPAL_VERSIONS = [
        // Current major, oldest supported minor.
        '10.0.0',
        '10.0.10',
        // Current minor.
        '10.1.0',
        '10.1.2',
        // Next minor dev.
        '11.x-dev',
    ];

    public static function setUpBeforeClass(): void
    {
        self::ensureFixtures();
    }

    /**
     * Tests for Composer compatability with supported Drupal core versions.
     *
     * @dataProvider providerDrupalVersionCompatability
     *
     * @see ../../composer.json
     */
    public function testDrupalVersionCompatability(string $versionConstraint): void
    {
        $fixtureDir = self::fixtureDir($versionConstraint);

        $process = new Process([
            'composer',
            'require',
            '--dry-run',
            'php-tuf/composer-stager:@dev',
        ], $fixtureDir);

        $process->run();

        self::assertTrue($process->isSuccessful(), sprintf(
            'Incompatible with Drupal core %s. Error output:%s%s ',
            $versionConstraint,
            PHP_EOL . PHP_EOL,
            $process->getErrorOutput(),
        ));
    }

    public function providerDrupalVersionCompatability(): array
    {
        $data = [];

        foreach (self::SUPPORTED_DRUPAL_VERSIONS as $datum) {
            $data[$datum] = [$datum];
        }

        return $data;
    }

    private static function fixtureDir(string $versionConstraint): string
    {
        return PathTestHelper::makeAbsolute(
            'drupal-' . $versionConstraint,
            PathTestHelper::testPersistentFixturesAbsolute(),
        );
    }

    private static function ensureFixtures(): void
    {
        foreach (self::SUPPORTED_DRUPAL_VERSIONS as $versionConstraint) {
            self::ensureFixture($versionConstraint);
        }
    }

    private static function ensureFixture(string $versionConstraint): void
    {
        if (self::fixtureExists($versionConstraint)) {
            return;
        }

        self::generateFixture($versionConstraint);
    }

    private static function fixtureExists(string $versionConstraint): bool
    {
        $fixtureDir = self::fixtureDir($versionConstraint);

        return file_exists($fixtureDir . '/composer.json');
    }

    public static function generateFixture(string $versionConstraint): void
    {
        $filesystem = new Filesystem();

        $fixtureDir = self::fixtureDir($versionConstraint);

        $filesystem->remove($fixtureDir);

        // Create drupal/recommended-project.
        (new Process([
            'composer',
            'create-project',
            '--no-install',
            'drupal/recommended-project:' . $versionConstraint,
            $fixtureDir,
        ]))->mustRun();

        // Require corresponding drupal/core-dev.
        (new Process([
            'composer',
            'require',
            '--no-install',
            'drupal/core-dev:' . $versionConstraint,
        ], $fixtureDir))->mustRun();

        // Add Composer Stager path repository.
        (new Process([
            'composer',
            'config',
            'repositories.php-tuf/composer-stager',
            'path',
            PathTestHelper::repositoryRootAbsolute(),
        ], $fixtureDir))->mustRun();
    }
}
