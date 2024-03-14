<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service;

final class TestUnfulfilledPrecondition extends AbstractTestPrecondition
{
    public const NAME = 'Test Unfulfilled Precondition';
    public const DESCRIPTION = 'The test unfulfilled precondition description.';
    public const IS_FULFILLED = false;
    public const STATUS_MESSAGE = self::UNFULFILLED_STATUS_MESSAGE;
    public const FULFILLED_STATUS_MESSAGE = 'The test unfulfilled precondition is unfulfilled.';
    public const UNFULFILLED_STATUS_MESSAGE = 'The test unfulfilled precondition is unfulfilled.';
}
