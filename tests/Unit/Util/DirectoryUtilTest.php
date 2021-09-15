<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Util;

use PhpTuf\ComposerStager\Util\DirectoryUtil;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Util\DirectoryUtil
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::ensureTrailingSlash
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::stripTrailingSlash
 */
class DirectoryUtilTest extends TestCase
{
    /**
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerStripTrailingSlash
     */
    public function testStripTrailingSlash($givenPath, $expectedPath): void
    {
        $actual = DirectoryUtil::stripTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerStripTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum',
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum',
                'expectedPath' => 'C:\Lorem\Ipsum',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum',
            ],
        ];
    }

    /**
     * @covers ::ensureTrailingSlash
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerEnsureTrailingSlash
     */
    public function testEnsureTrailingSlash($givenPath, $expectedPath): void
    {
        $actual = DirectoryUtil::ensureTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerEnsureTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum' . DIRECTORY_SEPARATOR,
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum' . DIRECTORY_SEPARATOR,
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum' . DIRECTORY_SEPARATOR,
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum' . DIRECTORY_SEPARATOR,
            ],
        ];
    }

    /**
     * @covers ::stripAncestor
     *
     * @dataProvider providerStripAncestor
     */
    public function testStripAncestor($path, $ancestor, $expected): void
    {
        $actual = DirectoryUtil::stripAncestor($path, $ancestor);

        self::assertEquals($expected, $actual);
    }

    public function providerStripAncestor(): array
    {
        // UNIX-like OS paths.
        if (DIRECTORY_SEPARATOR === '/') {
            return [
                [
                    'path' => '',
                    'ancestor' => '',
                    'expected' => '',
                ],
                [
                    'path' => 'lorem',
                    'ancestor' => 'ipsum',
                    'expected' => 'lorem',
                ],
                [
                    'path' => 'lorem/ipsum',
                    'ancestor' => 'lorem',
                    'expected' => 'ipsum',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'lorem/ipsum',
                    'expected' => 'dolor/sit/amet',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'ipsum/dolor',
                    'expected' => 'lorem/ipsum/dolor/sit/amet',
                ],
                [
                    'path' => 'lorem/ipsum/dolor/sit/amet',
                    'ancestor' => '/lorem/ipsum',
                    'expected' => 'lorem/ipsum/dolor/sit/amet',
                ],
                [
                    'path' => '/lorem/ipsum/dolor/sit/amet',
                    'ancestor' => 'lorem/ipsum',
                    'expected' => '/lorem/ipsum/dolor/sit/amet',
                ],
            ];
        }
        // Windows paths.
        return [
            [
                'path' => 'Lorem\Ipsum\Dolor\Sit\Amet',
                'ancestor' => 'Lorem\Ipsum',
                'expected' => 'Dolor\Sit\Amet',
            ],
            [
                'path' => 'C:\Lorem\Ipsum\Dolor\Sit',
                'ancestor' => 'Lorem\Ipsum',
                'expected' => 'C:\Lorem\Ipsum\Dolor\Sit',
            ],
            [
                'path' => 'C:\Lorem\Ipsum\Dolor\Sit',
                'ancestor' => 'C:\Lorem\Ipsum',
                'expected' => 'Dolor\Sit',
            ],
        ];
    }
}
