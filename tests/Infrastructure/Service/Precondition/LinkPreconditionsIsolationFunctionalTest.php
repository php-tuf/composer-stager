<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\TestCase;
use Throwable;

/**
 * Tests the interaction of unsupported links preconditions.
 *
 * @coversNothing
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
final class LinkPreconditionsIsolationFunctionalTest extends TestCase
{
    private const COVERED_PRECONDITIONS = [
        NoAbsoluteSymlinksExist::class,
        NoHardLinksExist::class,
        NoLinksExistOnWindows::class,
        NoSymlinksPointOutsideTheCodebase::class,
        NoSymlinksPointToADirectory::class,
    ];

    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);

        chdir($this->activeDir->resolved());
    }

    /** A NoUnsupportedLinksExist object can't be created directly because some preconditions need to be excluded. */
    protected function createTestPreconditionsTree(array $excludePreconditions = []): TestPreconditionsTree
    {
        $container = $this->getContainer();
        $container->compile();

        $allNoUnsupportedLinkPreconditions = [];
        $includedPreconditions = [];

        foreach ($container->getServiceIds() as $serviceId) {
            try {
                $service = $container->get($serviceId);
            } catch (Throwable) {
                // Ignore services that are unavailable in the testing context.
                continue;
            }

            // Limit to link iterating preconditions.
            if (!($service instanceof AbstractFileIteratingPrecondition)) {
                continue;
            }

            $allNoUnsupportedLinkPreconditions[] = $serviceId;

            // Exclude the SUT to ensure that it does not prevent other preconditions from being tested, along with
            // any preconditions that "overlap" with it (in the sense that they will also fail whenever the SUT does).
            if (in_array($serviceId, $excludePreconditions, true)) {
                continue;
            }

            $includedPreconditions[$service::class] = $service;
        }

        $uncoveredPreconditions = array_diff($allNoUnsupportedLinkPreconditions, self::COVERED_PRECONDITIONS);
        assert(
            $uncoveredPreconditions === [],
            reset($uncoveredPreconditions) . ' is not covered here. Add coverage and then add it to ::ALL_NO_UNSUPPORTED_LINKS_PRECONDITIONS',
        );

        return new TestPreconditionsTree(...$includedPreconditions);
    }

    public function testAllPassWithoutLinks(): void
    {
        $activeDir = $this->activeDir;
        $stagingDir = $this->stagingDir;
        $sut = $this->createTestPreconditionsTree();

        self::assertTrue($sut->isFulfilled($activeDir, $stagingDir), 'All preconditions passed together without any links present.');
    }

    /** @group no_windows */
    public function testNoAbsoluteSymlinksExist(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolved();
        $target = PathFactory::create('target.txt', $activeDir)->resolved();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoAbsoluteSymlinksExist::class);
    }

    /** @group windows_only */
    public function testNoLinksExistOnWindows(): void
    {
        $activeDir = $this->activeDir;
        $stagingDir = $this->stagingDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolved();
        $target = PathFactory::create('target.txt', $activeDir)->resolved();
        touch($target);
        symlink($target, $source);

        $container = $this->getContainer();
        $container->compile();
        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows $sut */
        $sut = $container->get(NoLinksExistOnWindows::class);

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $this->expectException(PreconditionException::class);
        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }

    /** @group no_windows */
    public function testNoSymlinksPointOutsideTheCodebase(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolved();
        $target = PathFactory::create('../target.txt', $activeDir)->raw();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointOutsideTheCodebase::class);
    }

    /** @group no_windows */
    public function testNoSymlinksPointToADirectory(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('link', $activeDir)->resolved();
        $target = PathFactory::create('directory', $activeDir)->raw();
        mkdir($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointToADirectory::class);
    }

    /** @group no_windows */
    public function testNoHardLinksExistExist(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolved();
        $target = PathFactory::create('target.txt', $activeDir)->resolved();
        touch($target);
        link($target, $source);

        $this->assertPreconditionIsIsolated(NoHardLinksExist::class);
    }

    /** @group no_windows */
    private function assertPreconditionIsIsolated(string $sut, array $conflictingPreconditions = []): void
    {
        $activeDir = $this->activeDir;
        $stagingDir = $this->stagingDir;
        $excludePreconditions = array_merge($conflictingPreconditions, [$sut]);
        $sut = $this->createTestPreconditionsTree($excludePreconditions);

        $sut->assertIsFulfilled($activeDir, $stagingDir);

        $this->expectNotToPerformAssertions();
    }
}
