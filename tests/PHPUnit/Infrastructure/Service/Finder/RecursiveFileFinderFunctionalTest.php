<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Finder;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder $fileFinder
 */
final class RecursiveFileFinderFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): RecursiveFileFinder
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder $fileFinder */
        $fileFinder = $container->get(RecursiveFileFinder::class);

        return $fileFinder;
    }

    /**
     * @covers ::find
     *
     * @dataProvider providerFind
     */
    public function testFind($files, $exclusions, $expected): void
    {
        $directory = PathFactory::create(self::ACTIVE_DIR);
        self::createFiles($directory->resolve(), $files);
        $sut = $this->createSut();

        $actual = $sut->find($directory, $exclusions);

        self::assertSame($expected, $actual);
    }

    public function providerFind(): array
    {
        return [
            [
                'files' => [],
                'exclusions' => null,
                'expected' => [],
            ],
            [
                'files' => [],
                'exclusions' => new PathList([]),
                'expected' => [],
            ],
            [
                'files' => [
                    'one.txt',
                    'two.txt',
                ],
                'exclusions' => new PathList([]),
                'expected' => $this->normalizePaths([
                    'one.txt',
                    'two.txt',
                ]),
            ],
            [
                'files' => [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
                'exclusions' => new PathList([]),
                'expected' => $this->normalizePaths([
                    'one.txt',
                    'three.txt',
                    'two.txt',
                ]),
            ],
            [
                'files' => ['excluded.txt'],
                'exclusions' => new PathList(['excluded.txt']),
                'expected' => $this->normalizePaths([]),
            ],
            [
                'files' => [
                    'included.txt',
                    'excluded.txt',
                ],
                'exclusions' => new PathList(['excluded.txt']),
                'expected' => $this->normalizePaths(['included.txt']),
            ],
            [
                'files' => [
                    'file_in_dir_root.txt',
                    'arbitrary_subdir/file.txt',
                    'somewhat/deeply/nested/file.txt',
                    'very/deeply/nested/file/one/two/three/four/five/six/seven/eight/nine/ten/eleven/twelve/thirteen/fourteen/fifteen.txt',
                    'long_filename_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
                    // Files excluded by exact pathname.
                    'EXCLUDED_file_in_dir_root.txt',
                    'arbitrary_subdir/EXCLUDED_file.txt',
                    // Files excluded by directory.
                    'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt',
                    'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
                    'another_EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
                    'arbitrary_subdir/with/nested/EXCLUDED_dir/with/a/file/in/it/that/is/NEVER/CHANGED/anywhere.txt',
                    // Files excluded by "hidden" directory, i.e., beginning with a "dot" (.), e.g., ".git".
                    '.hidden_EXCLUDED_dir/one.txt',
                    '.hidden_EXCLUDED_dir/two.txt',
                    '.hidden_EXCLUDED_dir/three.txt',
                ],
                'exclusions' => new PathList([
                    // Exact pathnames.
                    'EXCLUDED_file_in_dir_root.txt',
                    'arbitrary_subdir/EXCLUDED_file.txt',
                    // Directories.
                    'EXCLUDED_dir',
                    'arbitrary_subdir/with/nested/EXCLUDED_dir',
                    // Directory with a trailing slash.
                    'another_EXCLUDED_dir/',
                    // "Hidden" directory.
                    '.hidden_EXCLUDED_dir',
                    // Duplicative.
                    'EXCLUDED_file_in_dir_root.txt',
                    // Overlapping.
                    'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
                    // Non-existent.
                    'file_that_NEVER_EXISTS_anywhere.txt',
                ]),
                'expected' => $this->normalizePaths([
                    'file_in_dir_root.txt',
                    'arbitrary_subdir/file.txt',
                    'somewhat/deeply/nested/file.txt',
                    'very/deeply/nested/file/one/two/three/four/five/six/seven/eight/nine/ten/eleven/twelve/thirteen/fourteen/fifteen.txt',
                    'long_filename_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
                ]),
            ],
        ];
    }

    private function normalizePaths($paths): array
    {
        $paths = array_map(static function ($path) {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    self::TEST_WORKING_DIR,
                    self::ACTIVE_DIR,
                    $path,
                ],
            );

            return PathFactory::create($path)->resolve();
        }, $paths);

        sort($paths);

        return $paths;
    }
}
