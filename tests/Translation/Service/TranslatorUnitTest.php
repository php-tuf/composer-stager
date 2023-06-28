<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use AssertionError;
use Error;
use LogicException;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\LocaleInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxyInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslationParameters;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 *
 * @covers \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 */
final class TranslatorUnitTest extends TestCase
{
    private DomainOptionsInterface $domainOptions;
    private SymfonyTranslatorProxyInterface|ObjectProphecy $symfonyTranslatorProxy;
    private TranslatableFactoryInterface|ObjectProphecy $translatableFactory;

    public function setUp(): void
    {
        $this->domainOptions = new TestDomainOptions();
        $this->symfonyTranslatorProxy = new SymfonyTranslatorProxy();
        $this->translatableFactory = new TestTranslatableFactory();
    }

    private function createSut(): Translator
    {
        assert($this->symfonyTranslatorProxy instanceof SymfonyTranslatorProxyInterface);
        assert($this->translatableFactory instanceof TranslatableFactoryInterface);

        return new Translator($this->domainOptions, $this->symfonyTranslatorProxy);
    }

    /**
     * @covers ::__construct
     * @covers ::getLocale
     * @covers ::trans
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        string $message,
        ?TranslationParametersInterface $parameters,
        ?string $domain,
        ?string $locale,
        string $expectedTranslation,
    ): void {
        $sut = $this->createSut();

        $actualTranslation = $sut->trans($message, $parameters);

        self::assertEquals($expectedTranslation, $actualTranslation, 'Returned correct translation.');
        self::assertEquals(LocaleInterface::DEFAULT, $sut->getLocale(), 'Returned correct default locale.');
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Empty values' => [
                'message' => '',
                'parameters' => new TestTranslationParameters(),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => '',
            ],
            'Simple values' => [
                'message' => 'A string',
                'parameters' => new TestTranslationParameters(),
                'domain' => 'a_domain',
                'locale' => 'a_locale',
                'expectedTranslation' => 'A string',
            ],
            'Simple substitution' => [
                'message' => 'A %mood string',
                'parameters' => new TestTranslationParameters(['%mood' => 'happy']),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'message' => 'A %mood %size string',
                'parameters' => new TestTranslationParameters([
                    '%mood' => 'happy',
                    '%size' => 'little',
                ]),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy little string',
            ],
        ];
    }

    /**
     * @covers ::trans
     *
     * @dataProvider providerDomainHandling
     */
    public function testDomainHandling(string $defaultDomain, ?string $givenDomain, string $expectedDomain): void
    {
        $message = __METHOD__;
        $this->symfonyTranslatorProxy = $this->prophesize(SymfonyTranslatorProxyInterface::class);
        $this->symfonyTranslatorProxy
            ->trans(Argument::cetera())
            ->willReturn($message);
        $this->symfonyTranslatorProxy
            ->trans(Argument::any(), Argument::any(), $expectedDomain, Argument::cetera())
            ->shouldBeCalledOnce();
        $this->symfonyTranslatorProxy = $this->symfonyTranslatorProxy
            ->reveal();
        $this->domainOptions = new TestDomainOptions($defaultDomain);
        $sut = $this->createSut();

        $sut->trans($message, null, $givenDomain);
    }

    public function providerDomainHandling(): array
    {
        return [
            'Default' => [
                'defaultDomain' => 'One',
                'givenDomain' => null,
                'expectedDomain' => 'One',
            ],
            'Overridden via DomainOptions' => [
                'defaultDomain' => 'Two',
                'givenDomain' => null,
                'expectedDomain' => 'Two',
            ],
            'Overridden via ::trans() call' => [
                'defaultDomain' => 'Three',
                'givenDomain' => 'Overridden',
                'expectedDomain' => 'Overridden',
            ],
        ];
    }

    /** @covers ::create */
    public function testStaticFactory(): void
    {
        assert($this->symfonyTranslatorProxy instanceof SymfonyTranslatorProxy);

        $expected = new Translator(new DomainOptions(), $this->symfonyTranslatorProxy);

        $actual = Translator::create();

        self::assertEquals($expected, $actual, 'Created new translator.');
    }

    /**
     * @covers ::trans
     *
     * @dataProvider providerTranslatorException
     */
    public function testTranslatorException(Throwable $exception): void
    {
        $message = __METHOD__;
        $this->symfonyTranslatorProxy = $this->prophesize(SymfonyTranslatorProxyInterface::class);
        $this->symfonyTranslatorProxy
            ->trans(Argument::cetera())
            ->willThrow($exception);
        $this->symfonyTranslatorProxy = $this->symfonyTranslatorProxy
            ->reveal();
        $sut = $this->createSut();

        $expectedMessage = sprintf('Translation error: %s', $exception->getMessage());

        // Disable assertions so production error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 0);
        self::assertSame($expectedMessage, $sut->trans($message), 'Returned exception message on failure.');

        // Re-enable assertions so development error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 1);
        self::assertTranslatableException(static function () use ($sut, $message) {
            $sut->trans($message);
        }, AssertionError::class, $expectedMessage);
    }

    public function providerTranslatorException(): array
    {
        return [
            [
                new Error('An Error'),
            ],
            [
                new LogicException('A LogicException'),
            ],
        ];
    }
}
