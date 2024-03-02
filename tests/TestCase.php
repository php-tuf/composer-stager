<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestUtils\AssertTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelperTrait;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelperTrait;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends PHPUnitTestCase
{
    use AssertTrait;
    use FilesystemTestHelperTrait;
    use PathTestHelperTrait;
    use ProphecyTrait;
    use TranslationTestHelperTrait;

    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected static function createTestEnvironment(?string $activeDir = null): void
    {
        $activeDir ??= self::activeDirRelative();

        self::removeTestEnvironment();

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $workingDirAbsolute = self::testFreshFixturesDirAbsolute();
        $activeDirAbsolute = self::makeAbsolute($activeDir, $workingDirAbsolute);
        self::mkdir([$workingDirAbsolute, $activeDirAbsolute]);
        chdir($workingDirAbsolute);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(self::testFreshFixturesDirAbsolute())) {
            return;
        }

        try {
            $filesystem->remove(self::testFreshFixturesDirAbsolute());
        } catch (IOException) {
            // @todo Windows chokes on this every time, e.g.,
            //    | Failed to remove directory
            //    | "D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container":
            //    | rmdir(D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container):
            //    | Resource temporarily unavailable.
            //   Obviously, this error suppression is likely to bite us in the future
            //   even though it doesn't seem to cause any problems now. Fix it.
            // @ignoreException
        }
    }

    public static function createTestPreconditionException(
        string $message = '',
        ?TranslationParametersInterface $parameters = null,
    ): PreconditionException {
        return new PreconditionException(
            new TestPrecondition(),
            self::createTranslatableExceptionMessage(
                $message,
                $parameters,
            ),
        );
    }

    protected static function changeFile(string $dir, string $filename): void
    {
        $fileAbsolute = self::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($fileAbsolute, self::CHANGED_CONTENT);
        assert($result !== false, sprintf('Failed to change file: %s', $fileAbsolute));
    }

    protected static function getDirectoryContents(string $dir): array
    {
        $dir = self::ensureTrailingSlash($dir);
        $dirListing = self::getFlatDirectoryListing($dir);

        $contents = [];

        foreach ($dirListing as $pathAbsolute) {
            if (is_link($dir . $pathAbsolute)) {
                $contents[$pathAbsolute] = '';

                continue;
            }

            $contents[$pathAbsolute] = file_get_contents($dir . $pathAbsolute);
        }

        return $contents;
    }

    protected function normalizePaths(array $paths): array
    {
        $paths = array_map(static function ($path): string {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    self::testFreshFixturesDirAbsolute(),
                    self::activeDirRelative(),
                    $path,
                ],
            );

            return self::makeAbsolute($path, getcwd());
        }, $paths);

        sort($paths);

        return $paths;
    }
}
