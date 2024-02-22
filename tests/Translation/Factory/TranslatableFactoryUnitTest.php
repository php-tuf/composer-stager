<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service\TestDomainOptions;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory */
final class TranslatableFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::createDomainOptions
     */
    public function testCreateDomainOptions(): void
    {
        $expected = TranslationTestHelper::createDomainOptions();
        $sut = new TranslatableFactory($expected, TranslationTestHelper::createTranslator());

        $actual = $sut->createDomainOptions();

        self::assertSame($expected, $actual, 'Returned correct domain options object.');
    }

    /**
     * @covers ::createTranslatableMessage
     *
     * @dataProvider providerCreateTranslatableMessage
     */
    public function testCreateTranslatableMessage(
        array $givenCreateMessageArguments,
        TranslatableInterface $expectedMessage,
    ): void {
        $givenCreateMessageArguments = array_values($givenCreateMessageArguments);
        $sut = new TranslatableFactory(TranslationTestHelper::createDomainOptions(), TranslationTestHelper::createTranslator());

        $actual = $sut->createTranslatableMessage(...$givenCreateMessageArguments);

        self::assertEquals($expectedMessage, $actual, 'Returned correct translatable object.');
    }

    public function providerCreateTranslatableMessage(): array
    {
        return [
            'Minimum values' => [
                'givenCreateMessageArguments' => ['message' => 'Minimum values'],
                'expectedMessage' => new TranslatableMessage(
                    'Minimum values',
                    TranslationTestHelper::createTranslator(),
                ),
            ],
            'Nullable values' => [
                'givenCreateMessageArguments' => ['message' => 'Nullable values'],
                'expectedMessage' => new TranslatableMessage(
                    'Nullable values',
                    TranslationTestHelper::createTranslator(),
                    null,
                    null,
                ),
            ],
            'Simple values' => [
                'givenCreateMessageArguments' => [
                    'message' => 'Simple values',
                    'translationParameters' => TranslationTestHelper::createTranslationParameters(),
                    'domain' => 'domain',
                ],
                'expectedMessage' => new TranslatableMessage(
                    'Simple values',
                    TranslationTestHelper::createTranslator(),
                    TranslationTestHelper::createTranslationParameters(),
                    'domain',
                ),
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
        $sut = new TranslatableFactory(TranslationTestHelper::createDomainOptions(), TranslationTestHelper::createTranslator());
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
