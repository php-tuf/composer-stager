<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Tests\TestCase;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions */
final class DomainOptionsUnitTest extends TestCase
{
    /**
     * @covers ::default
     * @covers ::exceptions
     */
    public function testBasicFunctionality(): void
    {
        $sut = new DomainOptions();

        self::assertSame(self::DOMAIN_DEFAULT, $sut->default(), 'Returned correct default domain.');
        self::assertSame(self::DOMAIN_EXCEPTIONS, $sut->exceptions(), 'Returned correct typecast exceptions domain.');
    }
}
