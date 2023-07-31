<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\Benchmark;

use Generator;
use PhpBench\Attributes\AfterClassMethods;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\ParamProviders;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

#[BeforeClassMethods(['setUpBeforeClass'])]
#[AfterClassMethods(['tearDownAfterClass'])]
final class FileSyncerBench extends BenchCase
{
    #[ParamProviders(['providerSyncers', 'providerOperation'])]
    #[AfterMethods(['tearDownAfterBenchSync'])]
    public function benchSync(array $params): void
    {
        [$syncerClassFQN, $syncerClassName, $sourcePath] = [
            $params['syncerClassFQN'],
            $params['syncerClassName'],
            $params['sourcePath'],
        ];

        $sut = $this->container->get($syncerClassFQN);
        assert($sut instanceof FileSyncerInterface);

        $destinationAbsolute = self::getDestinationAbsolute($syncerClassName);
        $destinationPath = PathFactory::create($destinationAbsolute);

        $sut->sync($sourcePath, $destinationPath);
    }

    public function providerSyncers(): Generator
    {
        yield 'PhpFileSyncer' => [
            'syncerClassName' => 'PhpFileSyncer',
            'syncerClassFQN' => PhpFileSyncer::class,
        ];

        // Only test RsyncFileSyncer if rsync is available.
        // @phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ((new SymfonyExecutableFinder())->find('rsync') !== null) {
            yield 'RsyncFileSyncer' => [
                'syncerClassName' => 'RsyncFileSyncer',
                'syncerClassFQN' => RsyncFileSyncer::class,
            ];
        }
    }

    public function providerOperation(): Generator
    {
        yield 'begin' => [
            'operation' => 'begin',
            'sourcePath' => FixtureHelper::drupal9CodebasePath(),
        ];
        yield 'commit' => [
            'operation' => 'commit',
            'sourcePath' => FixtureHelper::drupal10CodebasePath(),
        ];
    }

    public function tearDownAfterBenchSync(array $params): void
    {
        [$operation, $syncerClassName] = [
            $params['operation'],
            $params['syncerClassName'],
        ];

        // Only clean up after the "commit" operations, because it
        // needs the directory from the earlier "begin" operation.
        if ($operation !== 'commit') {
            return;
        }

        $filesystem = new SymfonyFilesystem();
        $filesystem->remove(self::getDestinationAbsolute($syncerClassName));
    }

    public static function tearDownAfterClass(): void
    {
        $filesystem = new SymfonyFilesystem();
        $filesystem->remove(FixtureHelper::workingDirAbsolute());
    }

    private static function getDestinationAbsolute(mixed $syncerClassName): string
    {
        return SymfonyPath::makeAbsolute($syncerClassName, FixtureHelper::workingDirAbsolute());
    }
}
