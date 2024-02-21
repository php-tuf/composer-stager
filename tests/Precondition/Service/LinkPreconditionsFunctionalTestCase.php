<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

abstract class LinkPreconditionsFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        FilesystemTestHelper::createDirectories(PathTestHelper::stagingDirRelative());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): PreconditionInterface;

    /**
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition::doAssertIsFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition::findFiles
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::assertIsFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::doAssertIsFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::isFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows::exitEarly
     *
     * @dataProvider providerFulfilledDirectoryDoesNotExist
     */
    public function testFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    private function doTestFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Silently ignored non-existent directory');
    }

    final public function providerFulfilledDirectoryDoesNotExist(): array
    {
        $nonexistentDir = PathTestHelper::nonExistentDirPath();

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => PathTestHelper::stagingDirPath(),
            ],
            'Staging directory' => [
                'activeDir' => PathTestHelper::activeDirPath(),
                'stagingDir' => $nonexistentDir,
            ],
        ];
    }

    /**
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition::doAssertIsFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition::findFiles
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::isFulfilled
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist::assertIsSupportedFile
     * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows::exitEarly
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathTestHelper::activeDirPath(), PathTestHelper::stagingDirPath());

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    abstract public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void;

    final public function providerExclusions(): array
    {
        return [
            'No links or exclusions' => [
                'links' => [],
                'exclusions' => [],
                'shouldBeFulfilled' => true,
            ],
            'One link with one exact exclusion' => [
                'links' => ['link'],
                'exclusions' => ['link'],
                'shouldBeFulfilled' => true,
            ],
            'Multiple links with exact exclusions' => [
                'links' => ['one', 'two', 'three'],
                'exclusions' => ['one', 'two', 'three'],
                'shouldBeFulfilled' => true,
            ],
            'Multiple links in an excluded directory' => [
                'links' => ['directory/one', 'directory/two'],
                'exclusions' => ['directory'],
                'shouldBeFulfilled' => true,
            ],
            'One link with no exclusions' => [
                'links' => ['link'],
                'exclusions' => [],
                'shouldBeFulfilled' => false,
            ],
            'One link with a non-matching exclusion' => [
                'links' => ['link'],
                'exclusions' => ['non_match'],
                'shouldBeFulfilled' => false,
            ],
        ];
    }
}
