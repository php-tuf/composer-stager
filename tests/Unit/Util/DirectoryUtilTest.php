<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Util;

use PhpTuf\ComposerStager\Exception\LogicException;
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
     * @covers ::getDescendantRelativeToAncestor
     * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::ensureTrailingSlash
     * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::stripTrailingSlash
     *
     * @dataProvider providerGetDescendantRelativeToAncestor
     */
    public function testGetDescendantRelativeToAncestor($ancestor, $base, $expected): void
    {
        $actual = DirectoryUtil::getDescendantRelativeToAncestor($ancestor, $base);

        self::assertEquals($expected, $actual);
    }

    public function providerGetDescendantRelativeToAncestor(): array
    {
        return [
            [
                'ancestor' => '/lorem/ipsum',
                'descendant' => '/lorem/ipsum/dolor',
                'expected' => 'dolor',
            ],
            [
                'ancestor' => '../ipsum/dolor',
                'descendant' => '../ipsum/dolor/sit/amet',
                'expected' => 'sit/amet',
            ],
            [
                'ancestor' => 'dolor/sit',
                'descendant' => 'dolor/sit/amet',
                'expected' => 'amet',
            ],
            [
                'ancestor' => './',
                'descendant' => './sit',
                'expected' => 'sit',
            ],
            [
                'ancestor' => 'consectetur/',
                'descendant' => 'consectetur/adipiscing',
                'expected' => 'adipiscing',
            ],
            [
                'ancestor' => 'adipiscing',
                'descendant' => 'adipiscing/elit/',
                'expected' => 'elit',
            ],
            [
                'ancestor' => 'elit',
                'descendant' => 'elit/sed.txt',
                'expected' => 'sed.txt',
            ],
            [
                'ancestor' => 'sed',
                'descendant' => 'sed/do.txt/',
                'expected' => 'do.txt',
            ],
        ];
    }

    /**
     * @covers ::getDescendantRelativeToAncestor
     * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::ensureTrailingSlash
     * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil::stripTrailingSlash
     *
     * @dataProvider providerGetDescendantRelativeToAncestorError
     */
    public function testGetDescendantRelativeToAncestorError($ancestor, $descendant): void
    {
        $this->expectException(LogicException::class);

        DirectoryUtil::getDescendantRelativeToAncestor($ancestor, $descendant);
        // Descendant is outside ancestor.
    }

    public function providerGetDescendantRelativeToAncestorError(): array
    {
        return [
            [
                'ancestor' => '',
                'descendant' => '',
            ],
            [
                'ancestor' => 'lorem',
                'descendant' => 'lorem',
            ],
            [
                'ancestor' => 'ipsum/',
                'descendant' => 'ipsum',
            ],
            [
                'ancestor' => 'dolor',
                'descendant' => 'dolor/',
            ],
            [
                'ancestor' => 'sit/amet',
                'descendant' => 'sit',
            ],
            [
                'ancestor' => 'amet/consectetur',
                'descendant' => 'adipiscing',
            ],
        ];
    }
}
