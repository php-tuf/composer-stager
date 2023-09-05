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
use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;
use PhpTuf\ComposerStager\PHPBench\TestUtils\ProcessHelper;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;
use function assert;

#[BeforeClassMethods(['setUpBeforeClass'])]
#[AfterClassMethods(['tearDownAfterClass'])]
final class FileSyncerBench extends BenchCase
{
    private const BEGIN = 'begin';
    private const MAJOR_UPDATE = 'major update';
    private const MINOR_UPDATE = 'minor update';
    private const POINT_UPDATE = 'point update';

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
        $destinationPath = $this->pathFactory->create($destinationAbsolute);

        $sut->sync($sourcePath, $destinationPath, null, null, ProcessHelper::PROCESS_TIMEOUT);
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
            'operation' => self::BEGIN,
            'sourcePath' => FixtureHelper::drupalOriginalCodebasePath(),
        ];

        yield 'major_update' => [
            'operation' => self::MAJOR_UPDATE,
            'sourcePath' => FixtureHelper::drupalMajorUpdateCodebasePath(),
        ];

        yield 'minor_update' => [
            'operation' => self::MINOR_UPDATE,
            'sourcePath' => FixtureHelper::drupalMinorUpdateCodebasePath(),
        ];

        yield 'point_update' => [
            'operation' => self::POINT_UPDATE,
            'sourcePath' => FixtureHelper::drupalPointUpdateCodebasePath(),
        ];
    }

    public function tearDownAfterBenchSync(array $params): void
    {
        [$operation, $syncerClassName] = [
            $params['operation'],
            $params['syncerClassName'],
        ];

        // Only clean up after the minor update (final) operation,
        // because it needs the directory from the earlier ones.
        if ($operation !== self::MINOR_UPDATE) {
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
