<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation;

use PhpTuf\ComposerStager\Infrastructure\Factory\Translation\TranslatableFactory;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Factory\Translation\TranslatableFactory
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Translation\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters
 */
final class TranslatableFactoryUnitTest extends TestCase
{
    /**
     * @covers ::createTranslatableMessage
     *
     * @dataProvider providerCreateTranslatableMessage
     */
    public function testCreateTranslatableMessage(array $arguments): void
    {
        $arguments = array_values($arguments);
        $sut = new TranslatableFactory();
        $expected = new TranslatableMessage(...$arguments);

        $actual = $sut->createTranslatableMessage(...$arguments);

        self::assertEquals($expected, $actual, 'Returned correct translatable object.');
    }

    public function providerCreateTranslatableMessage(): array
    {
        return [
            'String message' => [
                ['message' => 'String message'],
            ],
            'Default values' => [
                [
                    'message' => 'Message',
                    'parameters' => null,
                    'domain' => null,
                ],
            ],
            'Simple values' => [
                [
                    'message' => 'Message',
                    'parameters' => new TestTranslationParameters(),
                    'domain' => 'domain',
                ],
            ],
        ];
    }

    /**
     * @covers ::createTranslationParameters
     *
     * @dataProvider providerCreateTranslationParameters
     */
    public function testCreateTranslationParameters(array $parameters): void
    {
        $sut = new TranslatableFactory();
        $expected = new TranslationParameters($parameters);

        $actual = $sut->createTranslationParameters($parameters);

        self::assertEquals($expected, $actual, 'Returned correct translation parameters object.');
    }

    public function providerCreateTranslationParameters(): array
    {
        return [
            'Empty array' => [[]],
            'Simple array' => [['%placeholder' => 'value']],
        ];
    }
}
