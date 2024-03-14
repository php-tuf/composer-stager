<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service;

final class TestFulfilledPrecondition extends AbstractTestPrecondition
{
    public const NAME = 'Test Fulfilled Precondition';
    public const DESCRIPTION = 'The test fulfilled precondition description.';
    public const IS_FULFILLED = true;
    public const STATUS_MESSAGE = self::FULFILLED_STATUS_MESSAGE;
    public const FULFILLED_STATUS_MESSAGE = 'The test fulfilled precondition is fulfilled.';
    public const UNFULFILLED_STATUS_MESSAGE = 'The test fulfilled precondition is unfulfilled.';
}
