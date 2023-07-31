<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\Benchmark;

use Generator;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\ParamProviders;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable;
use PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommitterPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable;
use PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagerPreconditions;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable;
use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;

#[BeforeClassMethods(['setUpBeforeClass'])]
final class PreconditionBench extends BenchCase
{
    #[ParamProviders(['providerTrees'])]
    public function benchTrees(array $params): void
    {
        $this->doBench($params);
    }

    public function providerTrees(): Generator
    {
        yield 'BeginnerPreconditions' => [BeginnerPreconditions::class];
        yield 'CommitterPreconditions' => [CommitterPreconditions::class];
        yield 'StagerPreconditions' => [StagerPreconditions::class];
        yield 'CleanerPreconditions' => [CleanerPreconditions::class];
    }

    #[ParamProviders(['providerIndividuals'])]
    public function benchIndividuals(array $params): void
    {
        $this->doBench($params);
    }

    public function providerIndividuals(): Generator
    {
        yield 'ActiveAndStagingDirsAreDifferent' => [ActiveAndStagingDirsAreDifferent::class];
        yield 'ActiveDirExists' => [ActiveDirExists::class];
        yield 'ActiveDirIsWritable' => [ActiveDirIsWritable::class];
        yield 'ComposerIsAvailable' => [ComposerIsAvailable::class];
        yield 'HostSupportsRunningProcesses' => [HostSupportsRunningProcesses::class];
        yield 'NoAbsoluteSymlinksExist' => [NoAbsoluteSymlinksExist::class];
        yield 'NoHardLinksExist' => [NoHardLinksExist::class];
        yield 'NoSymlinksPointOutsideTheCodebase' => [NoSymlinksPointOutsideTheCodebase::class];
        yield 'NoSymlinksPointToADirectory' => [NoSymlinksPointToADirectory::class];
        yield 'StagingDirDoesNotExist' => [StagingDirDoesNotExist::class];
        yield 'StagingDirExists' => [StagingDirExists::class];
        yield 'StagingDirIsWritable' => [StagingDirIsWritable::class];
    }

    private function doBench(array $params): void
    {
        $sut = $this->container->get(reset($params));
        assert($sut instanceof PreconditionInterface);

        $activeDir = FixtureHelper::drupal9CodebasePath();
        $stagingDir = FixtureHelper::drupal10CodebasePath();

        $sut->isFulfilled($activeDir, $stagingDir);
    }
}
