<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

final class TestException extends Exception implements ExceptionInterface
{
    public function getTranslatableMessage(): TranslatableInterface
    {
        return TranslationTestHelper::createTranslatableMessage($this->getMessage());
    }
}
