<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy */
final class SymfonyTranslatorProxyUnitTest extends TestCase
{
    /**
     * @covers ::getLocale
     * @covers ::trans
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(array $arguments, string $expectedTranslation): void
    {
        $arguments = array_values($arguments);
        $sut = new SymfonyTranslatorProxy();

        $actualTranslation = $sut->trans(...$arguments);
        $actualLocale = $sut->getLocale();

        self::assertEquals($expectedTranslation, $actualTranslation, 'Returned correct translation.');
        self::assertEquals(TranslationTestHelper::LOCALE_DEFAULT, $actualLocale, 'Got correct default locale.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Minimum values' => [
                'arguments' => ['message' => ''],
                'expectedTranslation' => '',
            ],
            'Empty values' => [
                'arguments' => [
                    'message' => '',
                    'parameters' => [],
                    'domain' => null,
                    'locale' => null,
                ],
                'expectedTranslation' => '',
            ],
            'Simple values' => [
                'arguments' => [
                    'message' => 'A string',
                    'parameters' => [],
                    'domain' => 'a_domain',
                    'locale' => 'a_locale',
                ],
                'expectedTranslation' => 'A string',
            ],
            'Simple substitution' => [
                'arguments' => [
                    'message' => 'A %mood string',
                    'parameters' => ['%mood' => 'happy'],
                    'domain' => null,
                    'locale' => null,
                ],
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'arguments' => [
                    'message' => 'A %mood %size string',
                    'parameters' => [
                        '%mood' => 'happy',
                        '%size' => 'little',
                    ],
                    'domain' => null,
                    'locale' => null,
                ],
                'expectedTranslation' => 'A happy little string',
            ],
        ];
    }

    /**
     * @covers ::symfonyTranslator
     * @covers ::trans
     */
    public function testSerializable(): void
    {
        $id = 'Arbitrary string';
        $sut = new SymfonyTranslatorProxy();

        self::assertSame($id, $sut->trans($id));

        $sut = serialize($sut);
        $sut = unserialize($sut);

        self::assertSame($id, $sut->trans($id));
    }
}
