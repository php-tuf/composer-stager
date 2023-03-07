<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractLinkIteratingPrecondition;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteLinksExist;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase;
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
        NoAbsoluteLinksExist::class,
        NoHardLinksExist::class,
        NoLinksExistOnWindows::class,
        NoSymlinksPointOutsideTheCodebase::class,
    ];

    public static function setUpBeforeClass(): void
    {
        if (!self::isWindows()) {
            return;
        }

        self::markTestSkipped('This test covers non-Windows functionality.');
    }

    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
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
            if (!($service instanceof AbstractLinkIteratingPrecondition)) {
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

    public function testNoAbsoluteLinksExist(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolve();
        $target = PathFactory::create('target.txt', $activeDir)->resolve();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoAbsoluteLinksExist::class);
    }

    public function testNoLinksExistOnWindows(): void
    {
        // @todo This test will require special handling since it's Windows-specific.
        $this->markTestIncomplete();
    }

    public function testNoSymlinksPointOutsideTheCodebase(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolve();
        $target = PathFactory::create('../target.txt', $activeDir)->raw();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointOutsideTheCodebase::class);
    }

    public function testNoHardLinksExistExist(): void
    {
        $activeDir = $this->activeDir;
        $source = PathFactory::create('source.txt', $activeDir)->resolve();
        $target = PathFactory::create('target.txt', $activeDir)->resolve();
        touch($target);
        link($target, $source);

        $this->assertPreconditionIsIsolated(NoHardLinksExist::class);
    }

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
