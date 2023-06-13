<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use AssertionError;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 *
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters
 */
final class TranslatableAwareTraitUnitTest extends TestCase
{
    /**
     * @covers ::setTranslatableFactory
     * @covers ::t
     *
     * @dataProvider providerT
     */
    public function testT(array $arguments): void
    {
        $arguments = array_values($arguments);
        $expected = new TestTranslatableMessage(...$arguments);
        $translatableFactory = new TestTranslatableFactory();

        $sut = new class($translatableFactory) extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;

            public function __construct(TranslatableFactoryInterface $translatableFactory)
            {
                $this->setTranslatableFactory($translatableFactory);
            }
        };

        $actual = $sut->callT(...$arguments);

        self::assertEquals($expected, $actual, 'Returned correct translatable object.');
    }

    public function providerT(): array
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

    /** @covers ::t */
    public function testTMissingTranslatableFactory(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('The "t()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');

        $sut = new class() extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;
        };

        $sut->callT('Message');
    }

    public function providerF(): array
    {
        return [
            'Empty string' => [
                'format' => '',
                'values' => [],
                'expected' => '',
            ],
            'Simple string' => [
                'format' => 'A simple string.',
                'values' => [],
                'expected' => 'A simple string.',
            ],
            'String with sprintf values' => [
                'format' => 'String: "%s". Decimal: %d.',
                'values' => [
                    'string',
                    42,
                ],
                'expected' => 'String: "string". Decimal: 42.',
            ],
        ];
    }

    /**
     * @covers ::p
     *
     * @dataProvider providerP
     */
    public function testP(array $parameters): void
    {
        $translatableFactory = new TestTranslatableFactory();

        $sut = new class($translatableFactory) extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;

            public function __construct(TranslatableFactoryInterface $translatableFactory)
            {
                $this->setTranslatableFactory($translatableFactory);
            }
        };

        $expected = new TranslationParameters($parameters);

        $actual = $sut->callP($parameters);

        self::assertEquals($expected, $actual);
    }

    public function providerP(): array
    {
        return [
            'Empty parameters' => [
                'parameters' => [],
            ],
            'Simple parameters' => [
                'parameters' => [
                    '%one' => 'one',
                    '%two' => 'two',
                ],
            ],
        ];
    }

    /** @covers ::p */
    public function testPMissingTranslatableFactory(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('The "p()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');

        $sut = new class() extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;
        };

        $sut->callP();
    }
}

abstract class AbstractTranslatableAwareClass
{
    use TranslatableAwareTrait;

    public function callT(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return $this->t($message, $parameters, $domain);
    }

    public function callP(array $parameters = []): TranslationParametersInterface
    {
        return $this->p($parameters);
    }
}
