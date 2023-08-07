<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exploratory;

use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * This class provides a template for temporary tests to quickly prove hypotheses or verify
 * assumptions during development, such as about undocumented behavior of third code or PHP itself.
 *
 * To use it, copy it to a file ending in "Test.php", e.g., "ExploratoryTest.php", so PHPUnit will find it. In order
 * to avoid dirtying the Git working directory or accidentally committing throwaway tests, all files except this one in
 * this directory are Git ignored. To commit a test permanently to the codebase, move it to a more appropriate location.
 *
 * @coversNothing
 */
final class ExploratoryTestTemplate extends TestCase
{
    public function test(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
