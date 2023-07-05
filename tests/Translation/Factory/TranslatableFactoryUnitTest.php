<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestDomainOptions;

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
        $sut = new TranslatableFactory($expected);

        $actual = $sut->createDomainOptions();

        self::assertSame($expected, $actual, 'Returned correct domain options object.');
    }

    public function providerCreateDomainOptions(): array
    {
        return [
            [new DomainOptions()],
            [new TestDomainOptions()],
            [new TestDomainOptions('one', 'two')],
        ];
    }

    /**
     * @covers ::createTranslatableMessage
     *
     * @dataProvider providerCreateTranslatableMessage
     */
    public function testCreateTranslatableMessage(array $arguments): void
    {
        $arguments = array_values($arguments);
        $sut = new TranslatableFactory(new TestDomainOptions());
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
        $sut = new TranslatableFactory(new TestDomainOptions());
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
