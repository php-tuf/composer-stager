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
}
