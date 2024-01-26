<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Exception;

use Exception;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Value\TestTranslatableMessage;

final class TestException extends Exception implements ExceptionInterface
{
    public function getTranslatableMessage(): TranslatableInterface
    {
        return new TestTranslatableMessage($this->getMessage());
    }
}
