<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use AssertionError;
use Error;
use LogicException;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Service\LocaleOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxyInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

#[CoversClass(SymfonyTranslatorProxy::class)]
#[CoversClass(Translator::class)]
final class TranslatorUnitTest extends TestCase
{
    private DomainOptionsInterface $domainOptions;
    private LocaleOptionsInterface $localeOptions;
    private SymfonyTranslatorProxyInterface|ObjectProphecy $symfonyTranslatorProxy;
    private TranslatableFactoryInterface|ObjectProphecy $translatableFactory;

    protected function setUp(): void
    {
        $this->domainOptions = self::createDomainOptions();
        $this->localeOptions = self::createLocaleOptions();
        $this->symfonyTranslatorProxy = self::createSymfonyTranslatorProxy();
        $this->translatableFactory = self::createTranslatableFactory();
    }

    private function createSut(): Translator
    {
        assert($this->symfonyTranslatorProxy instanceof SymfonyTranslatorProxyInterface);
        assert($this->translatableFactory instanceof TranslatableFactoryInterface);

        return new Translator($this->domainOptions, $this->localeOptions, $this->symfonyTranslatorProxy);
    }

    #[DataProvider('providerBasicFunctionality')]
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
        self::assertEquals(TestLocaleOptions::DEFAULT, $sut->getLocale(), 'Returned correct default locale.');
    }

    public static function providerBasicFunctionality(): array
    {
        return [
            'Empty values' => [
                'message' => '',
                'parameters' => self::createTranslationParameters(),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => '',
            ],
            'Simple values' => [
                'message' => 'A string',
                'parameters' => self::createTranslationParameters(),
                'domain' => 'a_domain',
                'locale' => 'a_locale',
                'expectedTranslation' => 'A string',
            ],
            'Simple substitution' => [
                'message' => 'A %mood string',
                'parameters' => self::createTranslationParameters(['%mood' => 'happy']),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy string',
            ],
            'Multiple substitutions' => [
                'message' => 'A %mood %size string',
                'parameters' => self::createTranslationParameters([
                    '%mood' => 'happy',
                    '%size' => 'little',
                ]),
                'domain' => null,
                'locale' => null,
                'expectedTranslation' => 'A happy little string',
            ],
        ];
    }

    #[DataProvider('providerDomainHandling')]
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

    public static function providerDomainHandling(): array
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

    #[DataProvider('providerLocaleHandling')]
    public function testLocaleHandling(
        string $defaultLocale,
        ?string $givenLocale,
        string $expectedDelegatedLocale,
        string $expectedGetLocale,
    ): void {
        $message = __METHOD__;
        $this->symfonyTranslatorProxy = $this->prophesize(SymfonyTranslatorProxyInterface::class);
        $this->symfonyTranslatorProxy
            ->trans(Argument::cetera())
            ->willReturn($message);
        $this->symfonyTranslatorProxy
            ->trans($message, Argument::any(), Argument::any(), $expectedDelegatedLocale)
            ->shouldBeCalledOnce();
        $this->symfonyTranslatorProxy = $this->symfonyTranslatorProxy
            ->reveal();
        $this->localeOptions = new TestLocaleOptions($defaultLocale);
        $sut = $this->createSut();

        $sut->trans($message, null, null, $givenLocale);

        self::assertSame($expectedGetLocale, $sut->getLocale(), 'Returned correct locale.');
    }

    public static function providerLocaleHandling(): array
    {
        return [
            'Default' => [
                'defaultLocale' => 'One',
                'givenLocale' => null,
                'expectedDelegatedLocale' => 'One',
                'expectedGetLocale' => 'One',
            ],
            'Overridden via LocaleOptions' => [
                'defaultLocale' => 'Two',
                'givenLocale' => null,
                'expectedDelegatedLocale' => 'Two',
                'expectedGetLocale' => 'Two',
            ],
            'Overridden via ::trans() call' => [
                'defaultLocale' => 'Three',
                'givenLocale' => 'Overridden',
                'expectedDelegatedLocale' => 'Overridden',
                'expectedGetLocale' => 'Three',
            ],
        ];
    }

    public function testStaticFactory(): void
    {
        $domainOptions = self::createDomainOptions();
        $localeOptions = self::createLocaleOptions();
        $symfonyTranslatorProxy = self::createSymfonyTranslatorProxy();
        $expected = new Translator($domainOptions, $localeOptions, $symfonyTranslatorProxy);

        $actual = Translator::create();

        self::assertEquals($expected, $actual, 'Created new translator.');
    }

    #[DataProvider('providerTranslatorException')]
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
        ini_set('zend.assertions', 0);
        self::assertSame($expectedMessage, $sut->trans($message), 'Returned exception message on failure.');

        // Re-enable assertions so development error-handling can be tested.
        ini_set('zend.assertions', 1);
        self::assertTranslatableException(static function () use ($sut, $message): void {
            $sut->trans($message);
        }, AssertionError::class, $expectedMessage);
    }

    public static function providerTranslatorException(): array
    {
        return [
            'Error' => [new Error('An Error')],
            'LogicException' => [new LogicException('A LogicException')],
        ];
    }
}

final class TestDomainOptions implements DomainOptionsInterface
{
    public function __construct(
        private readonly string $default = TranslationTestHelper::DOMAIN_DEFAULT,
        private readonly string $exceptions = TranslationTestHelper::DOMAIN_EXCEPTIONS,
    ) {
    }

    public function default(): string
    {
        return $this->default;
    }

    public function exceptions(): string
    {
        return $this->exceptions;
    }
}

final class TestLocaleOptions implements LocaleOptionsInterface
{
    public const DEFAULT = 'en_US';

    public function __construct(private readonly string $default = self::DEFAULT)
    {
    }

    public function default(): string
    {
        return $this->default;
    }
}
