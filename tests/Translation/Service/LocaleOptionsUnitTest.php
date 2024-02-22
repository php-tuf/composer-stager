<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Service\LocaleOptions */
final class LocaleOptionsUnitTest extends TestCase
{
    private const DEFAULT = 'en_US';

    /** @covers ::default */
    public function testBasicFunctionality(): void
    {
        $sut = TranslationTestHelper::createLocaleOptions();

        self::assertSame(self::DEFAULT, $sut->default(), 'Returned correct default locale.');
    }
}
