<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
abstract class LinkPreconditionsFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
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
        $nonexistentDir = self::TEST_WORKING_DIR . '/65eb69a274470dd84e9b5371f7e1e8c8';

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => self::STAGING_DIR,
            ],
            'Staging directory' => [
                'activeDir' => self::ACTIVE_DIR,
                'stagingDir' => $nonexistentDir,
            ],
        ];
    }

    /**
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isFulfilled
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

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
                'links' => ['link.txt'],
                'exclusions' => ['link.txt'],
                'shouldBeFulfilled' => true,
            ],
            'Multiple links with exact exclusions' => [
                'links' => ['one.txt', 'two.txt', 'three.txt'],
                'exclusions' => ['one.txt', 'two.txt', 'three.txt'],
                'shouldBeFulfilled' => true,
            ],
            'Multiple links in an excluded directory' => [
                'links' => ['directory/one.txt', 'directory/two.txt'],
                'exclusions' => ['directory'],
                'shouldBeFulfilled' => true,
            ],
            'One link with no exclusions' => [
                'links' => ['link.txt'],
                'exclusions' => [],
                'shouldBeFulfilled' => false,
            ],
            'One link with a non-matching exclusion' => [
                'links' => ['link.txt'],
                'exclusions' => ['non_match.txt'],
                'shouldBeFulfilled' => false,
            ],
        ];
    }
}
