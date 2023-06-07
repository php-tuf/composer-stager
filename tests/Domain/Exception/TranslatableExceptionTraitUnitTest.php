<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Exception;

use Exception;
use PhpTuf\ComposerStager\Domain\Exception\TranslatableExceptionTrait;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation\TestTranslatableMessage;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Exception\TranslatableExceptionTrait
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Translation\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters
 */
final class TranslatableExceptionTraitUnitTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getTranslatableMessage
     */
    public function testBasicFunctionality(): void
    {
        $message = new TestTranslatableMessage(__METHOD__);

        $sut = new class($message) extends Exception {
            use TranslatableExceptionTrait;
        };

        self::assertSame((string) $message, $sut->getMessage(), 'Returned correct untranslated message.');
        self::assertSame($message, $sut->getTranslatableMessage(), 'Returned correct translatable message.');
    }
}
