<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractFileIteratingPrecondition::class)]
final class AbstractFileIteratingPreconditionFunctionalTest extends TestCase
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

    private function createSut(): AbstractFileIteratingPrecondition
    {
        // The SUT in this case, being abstract, can't be instantiated directly. Use
        // `NoHardLinksExist`, which is easy to create fixtures for, to exercise by extension.
        return ContainerTestHelper::get(NoHardLinksExist::class);
    }

    #[DataProvider('providerFulfilled')]
    public function testFulfilled(array $files): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        self::touch($files, $activeDirPath->absolute());
        self::touch($files, $stagingDirPath->absolute());
        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDirPath, $stagingDirPath));
    }

    public static function providerFulfilled(): array
    {
        return [
            'Files' => [
                [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
            ],
            'No files' => [
                [],
            ],
        ];
    }

    public function testUnfulfilled(): void
    {
        // Use `NoHardLinksExist` to exercise `AbstractFileIteratingPrecondition` by extensions.
        $sut = ContainerTestHelper::get(NoHardLinksExist::class);

        // Make sure that when scanning the active directory for links it doesn't
        // recurse into the nested staging directory. Do this by putting unsupported
        // files in the staging directory and excluding them.
        $activeDir = self::activeDirPath();
        $stagingDir = self::createPath('staging-dir', self::activeDirAbsolute());
        self::touch(self::makeAbsolute('file.txt', $stagingDir->absolute()));
        self::createHardlinks(['link.txt' => 'file.txt'], $stagingDir->absolute());

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled, 'Found unsupported links.');
    }

    public function testWithStagingDirNestedUnderActiveDir(): void
    {
        // Use `NoHardLinksExist` to exercise `AbstractFileIteratingPrecondition` by extensions.
        $sut = ContainerTestHelper::get(NoHardLinksExist::class);

        // Make sure that when scanning the active directory for links it doesn't
        // recurse into the nested staging directory. Do this by putting unsupported
        // files in the staging directory and excluding them.
        $activeDir = self::activeDirPath();
        $stagingDir = self::createPath('staging-dir', self::activeDirAbsolute());
        self::touch(self::makeAbsolute('file.txt', $stagingDir->absolute()));
        self::createHardlinks(['link.txt' => 'file.txt'], $stagingDir->absolute());

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir, self::createPathList('link.txt', 'file.txt'));

        self::assertTrue($isFulfilled, 'Excluded nested staging directory while scanning parent active directory.');
    }

    #[DataProvider('providerWithAbsentDirectory')]
    public function testWithAbsentDirectory(PathInterface $activeDir, PathInterface $stagingDir): void
    {
        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDir, $stagingDir));
    }

    public static function providerWithAbsentDirectory(): array
    {
        return [
            'No active directory' => [
                'activeDir' => self::nonExistentDirPath(),
                'stagingDir' => self::stagingDirPath(),
            ],
            'No staging directory' => [
                'activeDir' => self::activeDirPath(),
                'stagingDir' => self::nonExistentDirPath(),
            ],
        ];
    }
}
