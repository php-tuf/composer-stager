<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Internal\Translation\Service\LocaleOptions;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LocaleOptions::class)]
final class LocaleOptionsUnitTest extends TestCase
{
    private const DEFAULT = 'en_US';

    public function testBasicFunctionality(): void
    {
        $sut = self::createLocaleOptions();

        self::assertSame(self::DEFAULT, $sut->default(), 'Returned correct default locale.');
    }
}
