<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Finder\Service;

use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;

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
        return ContainerTestHelper::get(FileFinder::class);
    }

    /**
     * @covers ::find
     * @covers ::getRecursiveDirectoryIterator
     *
     * @dataProvider providerFind
     */
    public function testFind(array $files, array $exclusions, array $expected): void
    {
        $expected = $this->normalizePaths($expected);
        FilesystemTestHelper::touch($files, self::activeDirAbsolute());
        $sut = $this->createSut();

        $actual = $sut->find(self::activeDirPath(), ...$exclusions);

        self::assertArrayEquals($expected, $actual);
    }

    public function providerFind(): array
    {
        return [
            'No files, no exclusions' => [
                'files' => [],
                'exclusions' => [],
                'expected' => [],
            ],
            'No files, null exclusions' => [
                'files' => [],
                'exclusions' => [null],
                'expected' => [],
            ],
            'No files, empty exclusions' => [
                'files' => [],
                'exclusions' => [self::createPathList()],
                'expected' => [],
            ],
            'Multiple files, null exclusions' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                ],
                'exclusions' => [null],
                'expected' => [
                    'one.txt',
                    'two.txt',
                ],
            ],
            'Multiple files, no exclusions' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
                'exclusions' => [self::createPathList()],
                'expected' => [
                    'one.txt',
                    'three.txt',
                    'two.txt',
                ],
            ],
            'Multiple files in directories, no exclusions' => [
                'files' => [
                    'one/two.txt',
                    'three/four.txt',
                ],
                'exclusions' => [],
                'expected' => [
                    'one/two.txt',
                    'three/four.txt',
                ],
            ],
            'One file excluded by name' => [
                'files' => ['excluded.txt'],
                'exclusions' => [self::createPathList('excluded.txt')],
                'expected' => [],
            ],
            'Multiple files, partially excluded by name' => [
                'files' => [
                    'included.txt',
                    'excluded.txt',
                ],
                'exclusions' => [self::createPathList('excluded.txt')],
                'expected' => ['included.txt'],
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
                'exclusions' => [
                    self::createPathList(
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
                ],
                'expected' => [
                    'file_in_dir_root.txt',
                    'arbitrary_subdir/file.txt',
                    'somewhat/deeply/nested/file.txt',
                    'very/deeply/nested/file/one/two/three/four/five/six/seven/eight/nine/ten/eleven/twelve/thirteen/fourteen/fifteen.txt',
                    'long_filename_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
                ],
            ],
        ];
    }

    public function testFindResultSorting(): void
    {
        $given = [
            'middle.txt',
            'zz_last.txt',
            '__first.txt',
        ];
        $expected = $this->normalizePaths([
            '__first.txt',
            'middle.txt',
            'zz_last.txt',
        ]);
        FilesystemTestHelper::touch($given, self::activeDirAbsolute());
        $sut = $this->createSut();

        $actual = $sut->find(self::activeDirPath());

        self::assertSame($expected, $actual);
    }
}
