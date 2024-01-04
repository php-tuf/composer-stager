<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Misc;

use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

/**
 * Tests assumptions about PHP built-in behavior.
 *
 * @coversNothing
 */
final class PhpAssumptionsFunctionalTest extends TestCase
{
    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    public function testCopyDoesNotCreateParents(): void
    {
        $fixtureDirAbsolute = PathHelper::testFreshFixturesDirAbsolute();
        FilesystemHelper::createDirectories($fixtureDirAbsolute);
        $sourceFile = PathHelper::makeAbsolute('source.txt', $fixtureDirAbsolute);
        $destinationFile = PathHelper::makeAbsolute('one/two/three/destination.txt', $fixtureDirAbsolute);
        FilesystemHelper::touch($sourceFile);

        $result = @copy($sourceFile, $destinationFile);

        self::assertFalse($result, "PHP's built-in copy() function does not create missing parent directories");
    }
}
