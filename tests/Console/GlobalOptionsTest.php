<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\GlobalOptions;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\GlobalOptions
 * @uses \PhpTuf\ComposerStager\Console\GlobalOptions::__construct
 * @uses \PhpTuf\ComposerStager\Console\GlobalOptions::resolveActiveDir
 * @uses \PhpTuf\ComposerStager\Console\GlobalOptions::resolveStagingDir
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
class GlobalOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
    }

    private function createSut(): GlobalOptions
    {
        $filesystem = $this->filesystem->reveal();
        return new GlobalOptions($filesystem);
    }

    /**
     * @covers ::__construct
     * @covers ::resolveActiveDir
     * @covers ::resolveStagingDir
     *
     * @dataProvider provider
     */
    public function test(
        $cwd,
        $activeDirGivenInput,
        $activeDirExpectedOutput,
        $stagingDirGivenInput,
        $stagingDirExpectedOutput
    ): void {
        $this->filesystem
            ->getcwd()
            ->willReturn($cwd);
        $sut = $this->createSut();

        $activeDirActualOutput = $sut->resolveActiveDir($activeDirGivenInput);
        $stagingDirActualOutput = $sut->resolveStagingDir($stagingDirGivenInput);

        self::assertSame($activeDirExpectedOutput, $activeDirActualOutput, 'Resolved active directory.');
        self::assertSame($stagingDirExpectedOutput, $stagingDirActualOutput, 'Resolved staging directory.');
    }

    public function provider(): array
    {
        return [
            // Defaults.
            [
                'cwd' => '/var/www',
                'activeDirGivenInput' => null,
                'activeDirExpectedOutput' => '/var/www',
                'stagingDirGivenInput' => null,
                'stagingDirExpectedOutput' => '/var/www/.composer_staging',
            ],
            // Values provided.
            [
                'cwd' => '/var/www2',
                'activeDirGivenInput' => '/lorem',
                'activeDirExpectedOutput' => '/lorem',
                'stagingDirGivenInput' => '/ipsum',
                'stagingDirExpectedOutput' => '/ipsum',
            ],
        ];
    }
}
