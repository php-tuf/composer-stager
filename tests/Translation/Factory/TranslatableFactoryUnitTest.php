<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestDomainOptions;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory */
final class TranslatableFactoryUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::createDomainOptions
     *
     * @dataProvider providerCreateDomainOptions
     */
    public function testCreateDomainOptions(DomainOptionsInterface $expected): void
    {
        $sut = new TranslatableFactory($expected, new TestTranslator());

        $actual = $sut->createDomainOptions();

        self::assertSame($expected, $actual, 'Returned correct domain options object.');
    }

    public function providerCreateDomainOptions(): array
    {
        return [
            'Default' => [new DomainOptions()],
            'Overridden' => [new TestDomainOptions('one', 'two')],
        ];
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
        $sut = new TranslatableFactory(new TestDomainOptions(), new TestTranslator());

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
                    new TestTranslator(),
                ),
            ],
            'Nullable values' => [
                'givenCreateMessageArguments' => ['message' => 'Nullable values'],
                'expectedMessage' => new TranslatableMessage(
                    'Nullable values',
                    new TestTranslator(),
                    null,
                    null,
                ),
            ],
            'Simple values' => [
                'givenCreateMessageArguments' => [
                    'message' => 'Simple values',
                    'translationParameters' => new TestTranslationParameters(),
                    'domain' => 'domain',
                ],
                'expectedMessage' => new TranslatableMessage(
                    'Simple values',
                    new TestTranslator(),
                    new TestTranslationParameters(),
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
        $sut = new TranslatableFactory(new TestDomainOptions(), new TestTranslator());
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
