<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractFileIteratingPrecondition::class)]
#[CoversClass(AbstractPrecondition::class)]
#[CoversClass(NoLinksExistOnWindows::class)]
#[CoversClass(NoHardLinksExist::class)]
abstract class LinkPreconditionsFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        self::mkdir(self::stagingDirRelative());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): PreconditionInterface;

    #[DataProvider('providerFulfilledDirectoryDoesNotExist')]
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

    final public static function providerFulfilledDirectoryDoesNotExist(): array
    {
        $nonexistentDir = self::nonExistentDirPath();

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => self::stagingDirPath(),
            ],
            'Staging directory' => [
                'activeDir' => self::activeDirPath(),
                'stagingDir' => $nonexistentDir,
            ],
        ];
    }

    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    abstract public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void;

    final public static function providerExclusions(): array
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
