<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestPreconditionsTree;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use Throwable;

/**
 * Tests the interaction of unsupported links preconditions.
 *
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 */
final class LinkPreconditionsIsolationFunctionalTest extends TestCase
{
    private const COVERED_PRECONDITIONS = [
        NoAbsoluteSymlinksExist::class,
        NoHardLinksExist::class,
        NoLinksExistOnWindows::class,
        NoSymlinksPointOutsideTheCodebase::class,
    ];

    private static function path(string $path): string
    {
        return self::makeAbsolute($path, self::activeDirAbsolute());
    }

    protected function setUp(): void
    {
        self::createTestEnvironment();
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    /** A NoUnsupportedLinksExist object can't be created directly because some preconditions need to be excluded. */
    private function createTestPreconditionsTree(array $excludePreconditions = []): TestPreconditionsTree
    {
        $container = ContainerTestHelper::container();
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
        $allPreconditionsAccountedFor = $uncoveredPreconditions === [];
        assert(
            $allPreconditionsAccountedFor,
            sprintf('%s is missing coverage here. Add coverage and then add it to ::ALL_NO_UNSUPPORTED_LINKS_PRECONDITIONS', reset($uncoveredPreconditions)),
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
        $source = self::path('source.txt');
        $target = self::path('target.txt');
        self::touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoAbsoluteSymlinksExist::class);
    }

    /** @group windows_only */
    public function testNoLinksExistOnWindows(): void
    {
        $source = self::path('source.txt');
        $target = self::path('target.txt');
        self::touch($target);
        symlink($target, $source);

        $sut = ContainerTestHelper::get(NoLinksExistOnWindows::class);
        assert($sut instanceof NoLinksExistOnWindows);

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class);
    }

    /** @group no_windows */
    public function testNoSymlinksPointOutsideTheCodebase(): void
    {
        $source = self::path('source.txt');
        $target = '../target.txt';
        touch($target);
        symlink($target, $source);

        $this->assertPreconditionIsIsolated(NoSymlinksPointOutsideTheCodebase::class);

        self::rm($target);
    }

    /** @group no_windows */
    public function testNoHardLinksExistExist(): void
    {
        $source = self::path('source.txt');
        $target = self::path('target.txt');
        touch($target);
        link($target, $source);

        $this->assertPreconditionIsIsolated(NoHardLinksExist::class);
    }

    /** @group no_windows */
    private function assertPreconditionIsIsolated(string $sut): void
    {
        $sut = $this->createTestPreconditionsTree([$sut]);

        $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());

        $this->expectNotToPerformAssertions();
    }
}
