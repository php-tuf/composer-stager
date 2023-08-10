<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Symfony\Component\Filesystem\Path as SymfonyPath;

abstract class LinkPreconditionsFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        FilesystemHelper::createDirectories(self::STAGING_DIR_RELATIVE);

        $this->activeDir = self::activeDirPath();
        $this->stagingDir = self::stagingDirPath();
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): PreconditionInterface;

    /** @dataProvider providerFulfilledDirectoryDoesNotExist */
    public function testFulfilledDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    final protected function doTestFulfilledDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $activeDir = PathFactory::create($activeDir);
        $stagingDir = PathFactory::create($stagingDir);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Silently ignored non-existent directory');
    }

    final public function providerFulfilledDirectoryDoesNotExist(): array
    {
        $nonexistentDir = SymfonyPath::makeAbsolute(
            '65eb69a274470dd84e9b5371f7e1e8c8',
            PathHelper::testWorkingDirAbsolute(),
        );

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => self::STAGING_DIR_RELATIVE,
            ],
            'Staging directory' => [
                'activeDir' => self::ACTIVE_DIR_RELATIVE,
                'stagingDir' => $nonexistentDir,
            ],
        ];
    }

    /**
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

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
