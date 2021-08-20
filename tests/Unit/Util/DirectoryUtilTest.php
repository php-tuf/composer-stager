<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Util;

use PhpTuf\ComposerStager\Util\DirectoryUtil;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Util\DirectoryUtil
 */
class DirectoryUtilTest extends TestCase
{
    /**
     * @covers ::stripTrailingSlash
     *
     * @dataProvider providerStripTrailingSeparator
     */
    public function testStripTrailingSeparator($givenPath, $expectedPath): void
    {
        $actual = DirectoryUtil::stripTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerStripTrailingSeparator(): array
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
     * @dataProvider providerEnsureTrailingSeparator
     */
    public function testEnsureTrailingSeparator($givenPath, $expectedPath): void
    {
        $actual = DirectoryUtil::ensureTrailingSlash($givenPath);

        self::assertEquals($expectedPath, $actual);
    }

    public function providerEnsureTrailingSeparator(): array
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
}
