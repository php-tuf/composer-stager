<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Util;

use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use PhpTuf\ComposerStager\Util\PathUtil;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Util\PathUtil
 * @uses \PhpTuf\ComposerStager\Util\PathUtil::ensureTrailingSlash
 * @uses \PhpTuf\ComposerStager\Util\PathUtil::stripTrailingSlash
 */
class PathUtilUnitTest extends TestCase
{
    /**
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerStripTrailingSlash
     */
    public function testStripTrailingSlash($givenPath, $expectedPath): void
    {
        $actual = PathUtil::stripTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerStripTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '',
                'expectedPath' => '',
            ],
            // UNIX-like paths:
            [
                'givenPath' => './',
                'expectedPath' => '.',
            ],
            [
                'givenPath' => '/',
                'expectedPath' => '/',
            ],
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum',
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum',
            ],
            // Traditional DOS paths:
            [
                'givenPath' => '.\\',
                'expectedPath' => '.',
            ],
            [
                'givenPath' => 'C:\\',
                'expectedPath' => 'C:\\',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum',
                'expectedPath' => 'C:\Lorem\Ipsum',
            ],
            [
                'givenPath' => 'h:\Lorem\Ipsum\\',
                'expectedPath' => 'h:\Lorem\Ipsum',
            ],
            [
                'givenPath' => 'h:',
                'expectedPath' => 'h:',
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
        self::fixSeparatorsMultiple($givenPath, $expectedPath);

        $actual = PathUtil::ensureTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerEnsureTrailingSlash(): array
    {
        return [
            [
                'givenPath' => '',
                'expectedPath' => './',
            ],
            [
                'givenPath' => '.',
                'expectedPath' => './',
            ],
            [
                'givenPath' => '/lorem/ipsum',
                'expectedPath' => '/lorem/ipsum/',
            ],
            [
                'givenPath' => '/lorem/ipsum/',
                'expectedPath' => '/lorem/ipsum/',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum\\',
            ],
            [
                'givenPath' => 'C:\Lorem\Ipsum\\',
                'expectedPath' => 'C:\Lorem\Ipsum\\',
            ],
        ];
    }

    /**
     * @covers ::getPathRelativeToAncestor
     *
     * @dataProvider providerGetPathRelativeToAncestor
     */
    public function testGetPathRelativeToAncestor($ancestor, $path, $expected): void
    {
        $actual = PathUtil::getPathRelativeToAncestor($path, $ancestor);

        self::assertEquals($expected, $actual);
    }

    public function providerGetPathRelativeToAncestor(): array
    {
        // UNIX-like OS paths.
        if (!self::isWindows()) {
            return [
                'Match: single directory depth' => [
                    'ancestor' => 'one',
                    'path'     => 'one/two',
                    'expected' =>     'two',
                ],
                'Match: multiple directories depth' => [
                    'ancestor' => 'one/two',
                    'path'     => 'one/two/three/four/five',
                    'expected' =>         'three/four/five',
                ],
                'No match: no shared ancestor' => [
                    'ancestor' => 'one/two',
                    'path'     => 'three/four/five/six/seven',
                    'expected' => 'three/four/five/six/seven',
                ],
                'No match: identical paths' => [
                    'ancestor' => 'one',
                    'path'     => 'one',
                    'expected' => 'one',
                ],
                'No match: ancestor only as absolute path' => [
                    'ancestor' => '/one/two',
                    'path'     => 'one/two/three/four/five',
                    'expected' => 'one/two/three/four/five',
                ],
                'No match: path only as absolute path' => [
                    'ancestor' => 'one/two',
                    'path'     => '/one/two/three/four/five',
                    'expected' => '/one/two/three/four/five',
                ],
                'No match: sneaky "near match"' => [
                    'ancestor' => 'one',
                    'path'     => 'one_two',
                    'expected' => 'one_two',
                ],
                'Special case: empty strings' => [
                    'ancestor' => '',
                    'path'     => '',
                    'expected' => '',
                ],
            ];
        }
        // Windows paths.
        return [
            'Match: single directory depth' => [
                'ancestor' => 'One',
                'path'     => 'One\\Two',
                'expected' =>      'Two',
            ],
            'Match: multiple directories depth' => [
                'ancestor' => 'One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' =>           'Three\\Four\\Five',
            ],
            'No match: no shared ancestor' => [
                'ancestor' => 'One\\Two',
                'path'     => 'Three\\Four\\Five\\Six\\Seven',
                'expected' => 'Three\\Four\\Five\\Six\\Seven',
            ],
            'No match: identical paths' => [
                'ancestor' => 'One',
                'path'     => 'One',
                'expected' => 'One',
            ],
            'No match: ancestor only as absolute path' => [
                'ancestor' => '\\One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' => 'One\\Two\\Three\\Four\\Five',
            ],
            'No match: path only as absolute path' => [
                'ancestor' => 'One\\Two',
                'path'     => 'C:\\One\\Two\\Three\\Four',
                'expected' => 'C:\\One\\Two\\Three\\Four',
            ],
            'No match: sneaky "near match"' => [
                'ancestor' => 'One',
                'path'     => 'One_Two',
                'expected' => 'One_Two',
            ],
            'Special case: empty strings' => [
                'ancestor' => '',
                'path'     => '',
                'expected' => '',
            ],
        ];
    }
}
