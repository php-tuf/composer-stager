<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TranslatableFactory::class)]
final class TranslatableFactoryUnitTest extends TestCase
{
    public function testCreateDomainOptions(): void
    {
        $expected = self::createDomainOptions();
        $sut = new TranslatableFactory($expected, self::createTranslator());

        $actual = $sut->createDomainOptions();

        self::assertSame($expected, $actual, 'Returned correct domain options object.');
    }

    #[DataProvider('providerCreateTranslatableMessage')]
    public function testCreateTranslatableMessage(
        array $givenCreateMessageArguments,
        TranslatableInterface $expectedMessage,
    ): void {
        $givenCreateMessageArguments = array_values($givenCreateMessageArguments);
        $sut = new TranslatableFactory(self::createDomainOptions(), self::createTranslator());

        $actual = $sut->createTranslatableMessage(...$givenCreateMessageArguments);

        self::assertEquals($expectedMessage, $actual, 'Returned correct translatable object.');
    }

    public static function providerCreateTranslatableMessage(): array
    {
        return [
            'Minimum values' => [
                'givenCreateMessageArguments' => ['message' => 'Minimum values'],
                'expectedMessage' => new TranslatableMessage(
                    'Minimum values',
                    self::createTranslator(),
                ),
            ],
            'Nullable values' => [
                'givenCreateMessageArguments' => ['message' => 'Nullable values'],
                'expectedMessage' => new TranslatableMessage(
                    'Nullable values',
                    self::createTranslator(),
                    null,
                    null,
                ),
            ],
            'Simple values' => [
                'givenCreateMessageArguments' => [
                    'message' => 'Simple values',
                    'translationParameters' => self::createTranslationParameters(),
                    'domain' => 'domain',
                ],
                'expectedMessage' => new TranslatableMessage(
                    'Simple values',
                    self::createTranslator(),
                    self::createTranslationParameters(),
                    'domain',
                ),
            ],
        ];
    }

    #[DataProvider('providerCreateTranslationParameters')]
    public function testCreateTranslationParameters(array $parameters): void
    {
        $sut = new TranslatableFactory(self::createDomainOptions(), self::createTranslator());
        $expected = new TranslationParameters($parameters);

        $actual = $sut->createTranslationParameters($parameters);

        self::assertEquals($expected, $actual, 'Returned correct translation parameters object.');
    }

    public static function providerCreateTranslationParameters(): array
    {
        return [
            'Empty array' => [[]],
            'Simple array' => [['%placeholder' => 'value']],
        ];
    }
}
