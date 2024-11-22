<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DomainOptions::class)]
final class DomainOptionsUnitTest extends TestCase
{
    public function testBasicFunctionality(): void
    {
        $sut = self::createDomainOptions();

        self::assertSame(TranslationTestHelper::DOMAIN_DEFAULT, $sut->default(), 'Returned correct default domain.');
        self::assertSame(TranslationTestHelper::DOMAIN_EXCEPTIONS, $sut->exceptions(), 'Returned correct typecast exceptions domain.');
    }
}
