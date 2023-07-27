<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\Benchmark;

use Generator;
use PhpBench\Attributes\AfterClassMethods;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\ParamProviders;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;

#[BeforeClassMethods(['setUpBeforeClass'])]
#[AfterClassMethods(['tearDownAfterClass'])]
final class FileSyncerBench extends BenchCase
{
    #[ParamProviders(['providerSyncers', 'providerOperation'])]
    public function benchSync(array $params): void
    {
        $syncerClassFQN = $params['syncerClassFQN'];
        $sut = $this->container->get($syncerClassFQN);
        assert($sut instanceof FileSyncerInterface);
        $syncerClassName = $params['syncerClassName'];

        $destinationAbsolute = SymfonyPath::makeAbsolute($syncerClassName, FixtureHelper::workingDirAbsolute());
        $destinationPath = PathFactory::create($destinationAbsolute);

        $sut->sync($params['sourcePath'], $destinationPath);
    }

    public function providerSyncers(): Generator
    {
        yield 'PhpFileSyncer' => ['syncerClassName' => 'PhpFileSyncer', 'syncerClassFQN' => PhpFileSyncer::class];
        yield 'RsyncFileSyncer' => ['syncerClassName' => 'RsyncFileSyncer', 'syncerClassFQN' => RsyncFileSyncer::class];
    }

    public function providerOperation(): Generator
    {
        yield 'begin' => ['operation' => 'begin', 'sourcePath' => FixtureHelper::drupal9CodebasePath()];
        yield 'commit' => ['operation' => 'commit', 'sourcePath' => FixtureHelper::drupal10CodebasePath()];
    }

    public static function tearDownAfterClass(): void
    {
        $filesystem = new SymfonyFilesystem();
        $filesystem->remove(FixtureHelper::workingDirAbsolute());
    }
}
