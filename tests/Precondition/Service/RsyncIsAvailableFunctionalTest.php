<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\RsyncIsAvailable;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class RsyncIsAvailableFunctionalTest extends TestCase
{
    public function testFulfilled(): void
    {
        $sut = ContainerTestHelper::get(RsyncIsAvailable::class);

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());
        $statusMessage = $sut->getStatusMessage(self::activeDirPath(), self::stagingDirPath());

        $message = 'Rsync is available.';
        self::assertSame($message, $statusMessage->trans());
        self::assertTrue($isFulfilled);
    }
}
