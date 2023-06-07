<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Translation;

use AssertionError;
use Error;
use LogicException;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Translation\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Infrastructure\Service\Translation\SymfonyTranslatorProxyInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslationParameters;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\Translation\SymfonyTranslatorProxy
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Translation\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters
 */
final class TranslatorUnitTest extends TestCase
{
    private SymfonyTranslatorProxyInterface|ObjectProphecy $symfonyTranslatorProxy;

    public function setUp(): void
    {
        $this->symfonyTranslatorProxy = new SymfonyTranslatorProxy();
    }

    private function createSut(): Translator
    {
        assert($this->symfonyTranslatorProxy instanceof SymfonyTranslatorProxyInterface);

        return new Translator($this->symfonyTranslatorProxy);
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
        self::assertEquals('en_US', $sut->getLocale(), 'Returned correct default locale.');
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
     * @covers ::__construct
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

        // Disable assertions so production error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 0);
        self::assertSame($exception->getMessage(), $sut->trans($message), 'Returned exception message on failure.');

        // Re-enable assertions so development error-handling can be tested.
        assert_options(ASSERT_ACTIVE, 1);
        self::assertTranslatableException(static function () use ($sut, $message) {
            $sut->trans($message);
        }, AssertionError::class, sprintf('Translation error: %s', $exception->getMessage()));
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
