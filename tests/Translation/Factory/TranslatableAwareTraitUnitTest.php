<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use AssertionError;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value\TestTranslationParameters;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait */
final class TranslatableAwareTraitUnitTest extends TestCase
{
    /** @covers ::d */
    public function testDMethod(): void
    {
        $domainOptions = new DomainOptions();
        $translator = new TestTranslator();
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

    /** @covers ::d */
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

    /**
     * @covers ::setTranslatableFactory
     * @covers ::t
     *
     * @dataProvider providerTMethod
     */
    public function testTMethod(array $arguments): void
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

    public function providerTMethod(): array
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
                    'parameters' => new TestTranslationParameters(),
                    'domain' => 'domain',
                ],
            ],
        ];
    }

    /** @covers ::t */
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

    /**
     * @covers ::p
     *
     * @dataProvider providerPMethod
     */
    public function testPMethod(array $parameters): void
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

    public function providerPMethod(): array
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
