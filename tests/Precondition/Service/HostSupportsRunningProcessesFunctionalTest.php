<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 */
final class HostSupportsRunningProcessesFunctionalTest extends TestCase
{
    private PathInterface $activeDir;
    private PathInterface $stagingDir;

    protected function setUp(): void
    {
        $this->activeDir = self::activeDirPath();
        $this->stagingDir = self::stagingDirPath();
    }

    private function createSut(): HostSupportsRunningProcesses
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses $sut */
        $sut = $container->get(HostSupportsRunningProcesses::class);

        return $sut;
    }

    /**
     * @covers ::assertIsFulfilled
     *
     * This test proves that the precondition correctly detects a failure from
     * the Symfony Process component when the the proc_open() function is
     * unavailable. Unfortunately, it is infeasible to simulate that condition
     * in tests since functions cannot be disabled at runtime (at least, not
     * without the runkit7 PECL extension), so it is skipped by default. It can
     * be run on a one-off basis by manually disabling proc_open() in php.ini:
     * ```ini
     * disable_functions = proc_open
     * ```
     */
    public function testUnfulfilled(): void
    {
        if (function_exists('proc_open')) {
            // Exit early instead of marking skipped so as to avoid being marked as risky.
            $this->expectNotToPerformAssertions();

            return;
        }

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        self::assertFalse($isFulfilled, 'Detected lack of support for running processes.');
    }
}
