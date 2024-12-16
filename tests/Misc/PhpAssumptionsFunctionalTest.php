<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Misc;

use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Tests assumptions about PHP built-in behavior.
 */
#[CoversNothing]
final class PhpAssumptionsFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    public function testCopyDoesNotCreateParents(): void
    {
        $sourceFile = self::makeAbsolute('source.txt');
        $destinationFile = self::makeAbsolute('one/two/three/destination.txt');
        self::touch($sourceFile);

        $result = @copy($sourceFile, $destinationFile);

        self::assertFalse($result, "PHP's built-in copy() function does not create missing parent directories");
    }
}
