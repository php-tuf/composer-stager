<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestPrecondition;

final class PreconditionTestHelper
{
    public static function createTestPreconditionException(
        string $message = '',
        ?TranslationParametersInterface $parameters = null,
    ): PreconditionException {
        return new PreconditionException(
            new TestPrecondition(),
            TranslationTestHelper::createTranslatableExceptionMessage(
                $message,
                $parameters,
            ),
        );
    }
}
