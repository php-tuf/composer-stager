<?php

namespace PhpTuf\ComposerStager\Tests\Console;

use PhpTuf\ComposerStager\Console\ApplicationOptions;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Console\ApplicationOptions
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::__construct
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::getActiveDir
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::getStagingDir
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::resolve
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::resolveActiveDir
 * @uses \PhpTuf\ComposerStager\Console\ApplicationOptions::resolveStagingDir
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class ApplicationOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
    }

    private function createSut(): ApplicationOptions
    {
        $filesystem = $this->filesystem->reveal();
        return new ApplicationOptions($filesystem);
    }

    /**
     * @covers ::__construct
     * @covers ::getActiveDir
     * @covers ::getStagingDir
     * @covers ::resolve
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

        $sut->resolve($activeDirGivenInput, $stagingDirGivenInput);

        self::assertSame($activeDirExpectedOutput, $sut->getActiveDir(), 'Resolved active directory.');
        self::assertSame($stagingDirExpectedOutput, $sut->getStagingDir(), 'Resolved staging directory.');
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
