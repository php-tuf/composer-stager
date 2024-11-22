<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use AssertionError;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class TranslatableAwareTraitUnitTest extends TestCase
{
    public function testDMethod(): void
    {
        $domainOptions = self::createDomainOptions();
        $translator = self::createTranslator();
        $translatableFactory = new TranslatableFactory($domainOptions, $translator);

        $sut = new class($translatableFactory) extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;

            public function __construct(TranslatableFactoryInterface $translatableFactory)
            {
                $this->setTranslatableFactory($translatableFactory);
            }
        };

        $actual = $sut->callD();

        self::assertSame($domainOptions, $actual);
    }

    public function testDMethodWithMissingTranslatableFactory(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('The "d()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');

        $sut = new class() extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;
        };

        $sut->callD();
    }

    #[DataProvider('providerTMethod')]
    public function testTMethod(array $arguments): void
    {
        $arguments = array_values($arguments);
        $expected = self::createTranslatableMessage(...$arguments);
        $translatableFactory = self::createTranslatableFactory();

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

    public static function providerTMethod(): array
    {
        return [
            'Minimum values' => [
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
                    'parameters' => self::createTranslationParameters(),
                    'domain' => 'domain',
                ],
            ],
        ];
    }

    public function testTMethodWithMissingTranslatableFactory(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('The "t()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');

        $sut = new class() extends AbstractTranslatableAwareClass {
            use TranslatableAwareTrait;
        };

        $sut->callT('Message');
    }

    #[DataProvider('providerPMethod')]
    public function testPMethod(array $parameters): void
    {
        $translatableFactory = self::createTranslatableFactory();

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

    public static function providerPMethod(): array
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

    public function testPMethodWithMissingTranslatableFactory(): void
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

    public function callD(): DomainOptionsInterface
    {
        return $this->d();
    }

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
