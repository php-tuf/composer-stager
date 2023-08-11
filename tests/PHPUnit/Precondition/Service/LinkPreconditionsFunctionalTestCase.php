<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

abstract class LinkPreconditionsFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
        FilesystemHelper::createDirectories(PathHelper::stagingDirRelative());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): PreconditionInterface;

    /** @dataProvider providerFulfilledDirectoryDoesNotExist */
    public function testFulfilledDirectoryDoesNotExist(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    final protected function doTestFulfilledDirectoryDoesNotExist(
        PathInterface $activeDir,
        PathInterface $stagingDir,
    ): void {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Silently ignored non-existent directory');
    }

    final public function providerFulfilledDirectoryDoesNotExist(): array
    {
        $nonexistentDir = PathFactory::create('65eb69a274470dd84e9b5371f7e1e8c8');

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => PathHelper::stagingDirPath(),
            ],
            'Staging directory' => [
                'activeDir' => PathHelper::activeDirPath(),
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

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

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
