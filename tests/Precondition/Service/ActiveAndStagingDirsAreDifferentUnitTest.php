<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActiveAndStagingDirsAreDifferent::class)]
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active and staging directories are different';
    protected const DESCRIPTION = 'The active and staging directories cannot be the same.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active and staging directories are different.';

    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        $environment = $this->environment->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new ActiveAndStagingDirsAreDifferent($environment, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled(
            'The active and staging directories are different.',
            self::activeDirPath(),
            self::stagingDirPath(),
        );
    }

    public function testUnfulfilled(): void
    {
        $message = 'The active and staging directories are the same.';
        $samePath = self::activeDirPath();

        $this->doTestUnfulfilled($message, null, $samePath, $samePath);
    }
}
