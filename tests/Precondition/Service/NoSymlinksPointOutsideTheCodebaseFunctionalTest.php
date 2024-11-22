<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(NoSymlinksPointOutsideTheCodebase::class)]
#[Group('no_windows')]
final class NoSymlinksPointOutsideTheCodebaseFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        return ContainerTestHelper::get(NoSymlinksPointOutsideTheCodebase::class);
    }

    #[DataProvider('providerFulfilledWithValidLink')]
    public function testFulfilledWithValidLink(string $link, string $target): void
    {
        $activeDirPath = self::activeDirPath();
        $activeDirAbsolute = self::activeDirAbsolute();
        $stagingDirPath = self::stagingDirPath();

        $link = self::makeAbsolute($link, $activeDirAbsolute);
        self::ensureParentDirectory($link);
        $target = self::makeAbsolute($target, $activeDirAbsolute);
        self::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    public static function providerFulfilledWithValidLink(): array
    {
        return [
            'Not in any package' => [
                'link' => 'link.txt',
                'target' => 'target.txt',
            ],
            'Pointing within a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing into a package' => [
                'link' => 'link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing out of a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'target.txt',
            ],
            'Pointing from one package to another' => [
                'link' => 'vendor/package1/link.txt',
                'target' => 'vendor/package2/target.txt',
            ],
            'Weird relative paths' => [
                'link' => 'some/absurd/subdirectory/../with/../../a/link.txt',
                'target' => 'another/../weird/../arbitrary/target.txt',
            ],
        ];
    }

    #[DataProvider('providerUnfulfilled')]
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $target = self::makeAbsolute('target.txt', $targetDir);
        $link = self::makeAbsolute('link.txt', $linkDir);
        self::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains links that point outside the codebase, which is not supported. The first one is %s.',
            $linkDirName,
            self::makeAbsolute($linkDir, getcwd()),
            $link,
        );
        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, $message);
    }

    public static function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => self::testFreshFixturesDirAbsolute(),
                'linkDir' => self::activeDirAbsolute(),
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => self::testFreshFixturesDirAbsolute(),
                'linkDir' => self::stagingDirAbsolute(),
                'linkDirName' => 'staging',
            ],
        ];
    }

    public function testWithHardLink(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $basePathAbsolute = self::activeDirAbsolute();
        $link = self::makeAbsolute('link.txt', $basePathAbsolute);
        $target = self::makeAbsolute('target.txt', $basePathAbsolute);
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        self::touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Ignored hard link.');
    }

    public function testWithAbsoluteLink(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $dirPathAbsolute = self::activeDirAbsolute();
        $linkAbsolute = self::makeAbsolute('link.txt', $dirPathAbsolute);
        $targetAbsolute = self::makeAbsolute('target.txt', $dirPathAbsolute);
        $parentDir = dirname($linkAbsolute);
        @mkdir($parentDir, 0777, true);
        self::touch($targetAbsolute);
        symlink($targetAbsolute, $linkAbsolute);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled, 'Ignored hard link.');
    }

    #[DataProvider('providerExclusions')]
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = '../';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = self::createPathList(...$exclusions);
        $dirPath = self::activeDirAbsolute();
        self::createSymlinks($links, $dirPath);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
