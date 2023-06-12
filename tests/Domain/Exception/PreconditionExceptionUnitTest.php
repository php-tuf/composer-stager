<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Exception;

use Exception;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Tests\Precondition\Service\TestPrecondition;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

final class PreconditionExceptionUnitTest extends TestCase
{
    /** @covers \PhpTuf\ComposerStager\Domain\Exception\PreconditionException */
    public function testBasicFunctionality(): void
    {
        $message = 'Message';
        $translatableMessage = new TestTranslatableMessage($message);
        $code = 42;
        $previous = new Exception();
        $precondition = new TestPrecondition();
        $sut = new PreconditionException($precondition, $translatableMessage, $code, $previous);

        self::assertSame($precondition, $sut->getPrecondition(), 'Got precondition.');
        self::assertSame($message, $sut->getMessage(), 'Got untranslated message.');
        self::assertSame($translatableMessage, $sut->getTranslatableMessage(), 'Got translatable message.');
        self::assertSame($code, $sut->getCode(), 'Got code.');
        self::assertSame($previous, $sut->getPrevious(), 'Got previous exception.');
    }
}
