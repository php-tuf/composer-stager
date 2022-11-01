<?php declare(strict_types=1);

namespace PHPUnit\Misc;

use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * This class provides scaffolding for temporary tests to quickly prove
 * hypotheses or verify assumptions during development, such as about
 * undocumented behavior of third code or PHP itself.
 *
 * @coversNothing
 */
final class ExploratoryTest extends TestCase
{
    public function test(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
