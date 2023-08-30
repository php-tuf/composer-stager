<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 *
 * @covers ::__construct
 */
final class FileFinderFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment();
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): FileFinder
    {
        $container = ContainerHelper::container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder $fileFinder */
        $fileFinder = $container->get(FileFinder::class);

        return $fileFinder;
    }

    /**
     * @covers ::find
     *
     * @dataProvider providerFind
     */
    public function testFind(array $files, ?PathListInterface $exclusions, array $expected): void
    {
        self::createFiles(PathHelper::activeDirAbsolute(), $files);
        $sut = $this->createSut();

        $actual = $sut->find(PathHelper::activeDirPath(), $exclusions);

        self::assertSame($expected, $actual);
    }

    public function providerFind(): array
    {
        return [
            'No files, null exclusions' => [
                'files' => [],
                'exclusions' => null,
                'expected' => [],
            ],
            'No files, no exclusions' => [
                'files' => [],
                'exclusions' => new PathList(),
                'expected' => [],
            ],
            'Multiple files, null exclusions' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                ],
                'exclusions' => null,
                'expected' => $this->normalizePaths([
                    'one.txt',
                    'two.txt',
                ]),
            ],
            'Multiple files, no exclusions' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
                'exclusions' => new PathList(),
                'expected' => $this->normalizePaths([
                    'one.txt',
                    'three.txt',
                    'two.txt',
                ]),
            ],
            'One file excluded by name' => [
                'files' => ['excluded.txt'],
                'exclusions' => new PathList('excluded.txt'),
                'expected' => $this->normalizePaths([]),
            ],
            'Multiple files, partially excluded by name' => [
                'files' => [
                    'included.txt',
                    'excluded.txt',
                ],
                'exclusions' => new PathList('excluded.txt'),
                'expected' => $this->normalizePaths(['included.txt']),
            ],
            'Complex scenario' => [
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
                'exclusions' => new PathList(
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
                ),
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

    private function normalizePaths(array $paths): array
    {
        $paths = array_map(static function ($path): string {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    PathHelper::testWorkingDirAbsolute(),
                    PathHelper::activeDirRelative(),
                    $path,
                ],
            );

            return PathHelper::makeAbsolute($path, getcwd());
        }, $paths);

        sort($paths);

        return $paths;
    }
}
