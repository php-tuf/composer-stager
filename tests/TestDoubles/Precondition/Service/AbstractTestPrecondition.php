<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition;
use PhpTuf\ComposerStager\Tests\TestCase;

abstract class AbstractTestPrecondition extends AbstractPrecondition
{
    // Override in subclasses.
    public const NAME = 'NAME';
    public const DESCRIPTION = 'DESCRIPTION';
    public const IS_FULFILLED = true;
    public const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';
    public const UNFULFILLED_STATUS_MESSAGE = 'UNFULFILLED_STATUS_MESSAGE';

    protected function doAssertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void {
        if (!static::IS_FULFILLED) {
            throw TestCase::createTestPreconditionException(static::UNFULFILLED_STATUS_MESSAGE);
        }
    }

    public function getName(): TranslatableInterface
    {
        return $this->t(static::NAME);
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t(static::DESCRIPTION);
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t(static::FULFILLED_STATUS_MESSAGE);
    }
}
