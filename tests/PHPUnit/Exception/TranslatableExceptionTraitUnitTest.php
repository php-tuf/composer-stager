<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

/** @coversDefaultClass \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait */
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
