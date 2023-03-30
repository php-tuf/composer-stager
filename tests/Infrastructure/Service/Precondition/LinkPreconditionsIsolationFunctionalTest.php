<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
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

    private static function path(string $path): PathInterface
    {
        return PathFactory::create($path, self::activeDirPath());
    }

    protected function setUp(): void
    {
        self::createTestEnvironment();
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
        $sut = $this->createTestPreconditionsTree();

        self::assertTrue(
            $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath()),
            'All preconditions passed together without any links present.',
        );
    }

    /** @group no_windows */
    public function testNoAbsoluteSymlinksExist(): void
    {
        $source = self::path('source.txt')->resolved();
        $target = self::path('target.txt')->resolved();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoAbsoluteSymlinksExist::class);
    }

    /** @group windows_only */
    public function testNoLinksExistOnWindows(): void
    {
        $source = self::path('source.txt')->resolved();
        $target = self::path('target.txt')->resolved();
        touch($target);
        symlink($target, $source);

        $container = $this->getContainer();
        $container->compile();
        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows $sut */
        $sut = $container->get(NoLinksExistOnWindows::class);

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $this->expectException(PreconditionException::class);
        $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
    }

    /** @group no_windows */
    public function testNoSymlinksPointOutsideTheCodebase(): void
    {
        $source = self::path('source.txt')->resolved();
        $target = self::path('../target.txt')->raw();
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointOutsideTheCodebase::class);
    }

    /** @group no_windows */
    public function testNoSymlinksPointToADirectory(): void
    {
        $source = self::path('link')->resolved();
        $target = self::path('directory')->raw();
        mkdir($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointToADirectory::class);
    }

    /** @group no_windows */
    public function testNoHardLinksExistExist(): void
    {
        $source = self::path('source.txt')->resolved();
        $target = self::path('target.txt')->resolved();
        touch($target);
        link($target, $source);

        $this->assertPreconditionIsIsolated(NoHardLinksExist::class);
    }

    /** @group no_windows */
    private function assertPreconditionIsIsolated(string $sut, array $conflictingPreconditions = []): void
    {
        $excludePreconditions = array_merge($conflictingPreconditions, [$sut]);
        $sut = $this->createTestPreconditionsTree($excludePreconditions);

        $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());

        $this->expectNotToPerformAssertions();
    }
}
