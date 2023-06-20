<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Finds missing or incorrect "@api" and "@internal" docblock tags.
 *
 * The fixer is crude and doesn't cover all major cases, so the results should be examined carefully.
 *
 * phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
 */
final class APITagsSniff implements Sniff
{
    private const CODE_MISSING_TAG = 'Missing%sTag';
    private const CODE_UNEXPECTED_TAG = 'Unexpected%sTag';

    private const RULES = [
        'PhpTuf\\ComposerStager\\API\\' => [
            self::RULE_EXPECTED_TAG_NAME => '@api',
            self::RULE_EXPECTED_TAG_VALUE => 'This %s is subject to our backward compatibility promise and may be safely depended upon.',
            self::RULE_FORBIDDEN_TAG_NAME => '@internal',
        ],
        'PhpTuf\\ComposerStager\\Internal\\' => [
            self::RULE_EXPECTED_TAG_NAME => '@internal',
            self::RULE_EXPECTED_TAG_VALUE => "Don't depend directly on this %s. It may be changed or removed at any time without notice.",
            self::RULE_FORBIDDEN_TAG_NAME => '@api',
        ],
    ];

    private const RULE_EXPECTED_TAG_NAME = 'RULE_EXPECTED_TAG_NAME';
    private const RULE_EXPECTED_TAG_VALUE = 'RULE_EXPECTED_TAG_VALUE';
    private const RULE_FORBIDDEN_TAG_NAME = 'RULE_FORBIDDEN_TAG_NAME';

    private array $tokens = [];
    private File $phpcsFile;
    private int $scopePtr;

    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->phpcsFile = $phpcsFile;
        $this->scopePtr = $stackPtr;

        $rule = $this->getApplicableRule();

        // Return early if no rules apply.
        if ($rule === false) {
            return;
        }

        $this->tokens = $this->phpcsFile->getTokens();
        $currentTokenName = $this->getTagName($this->scopePtr);

        $expectedTagName = $rule[self::RULE_EXPECTED_TAG_NAME];
        $expectedTagValue = sprintf($rule[self::RULE_EXPECTED_TAG_VALUE], $currentTokenName);
        $forbiddenTagName = $rule[self::RULE_FORBIDDEN_TAG_NAME];

        $docblockTags = $this->getDocblockTags();
        $found = false;

        foreach ($docblockTags as $currentTagPtr => $tagEndPtr) {
            $currentTagName = $this->getTagName($currentTagPtr);

            // Found an unexpected tag.
            if ($currentTagName === $forbiddenTagName) {
                $fix = $this->phpcsFile->addFixableError(sprintf(
                    '%s docblock contains unexpected tag: "%s"',
                    ucfirst($currentTokenName),
                    $currentTagName,
                ), $currentTagPtr, $this->formatErrorCode(self::CODE_UNEXPECTED_TAG, $currentTagName));

                if ($fix) {
                    $this->removeTag($currentTagPtr, $tagEndPtr);
                }
            }

            if ($currentTagName !== $expectedTagName) {
                continue;
            }

            $currentTagValue = $this->getTagValue($currentTagPtr, $tagEndPtr);

            // Found the correct tag with the correct value.
            if ($currentTagValue === $expectedTagValue) {
                $found = true;

                continue;
            }

            // Found the correct tag with the wrong value.
            $fix = $this->phpcsFile->addFixableError(sprintf(
                '%s docblock contains "%s" tag with unexpected value: "%s"',
                ucfirst($currentTokenName),
                $currentTagName,
                $currentTagValue,
            ), $currentTagPtr, $this->formatErrorCode(self::CODE_UNEXPECTED_TAG, $currentTagName));

            if (!$fix) {
                continue;
            }

            $this->removeTag($currentTagPtr, $tagEndPtr);
        }

        if ($found) {
            return;
        }

        $fix = $this->phpcsFile->addFixableError(sprintf(
            '%s docblock is missing required tag: "%s %s"',
            ucfirst($currentTokenName),
            $expectedTagName,
            $expectedTagValue,
        ), $this->scopePtr, $this->formatErrorCode(self::CODE_MISSING_TAG, $expectedTagName));

        if (!$fix) {
            return;
        }

        $this->addExpectedTag($expectedTagName, $expectedTagValue);
    }

    /** Gets the scope docblock open tag pointer. */
    public function getDocblockOpenPtr(): int|false
    {
        return $this->phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $this->scopePtr, false, false, '/**', true);
    }

    /** Gets the docblock closer pointer. */
    public function getDocblockCloserPtr(): int
    {
        $docblockOpenerPtr = $this->getDocblockOpenPtr();
        $docblockOpener = $this->tokens[$docblockOpenerPtr];

        return $docblockOpener['comment_closer'];
    }

    private function getApplicableRule(): array|false
    {
        $namespace = $this->getNamespace() . '\\';

        foreach (array_keys(self::RULES) as $rule) {
            if (str_starts_with($namespace, $rule)) {
                return self::RULES[$rule];
            }
        }

        return false;
    }

    /** @see \PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\SelfMemberReferenceSniff::getNamespaceOfScope() */
    private function getNamespace(): string
    {
        $namespace = '\\';
        $namespaceDeclaration = $this->phpcsFile->findPrevious(T_NAMESPACE, $this->scopePtr);

        if ($namespaceDeclaration !== false) {
            $endOfNamespaceDeclaration = $this->phpcsFile->findNext(
                [T_SEMICOLON, T_OPEN_CURLY_BRACKET],
                $namespaceDeclaration,
            );
            $namespace = $this->getDeclarationNameWithNamespace(
                $this->phpcsFile->getTokens(),
                $endOfNamespaceDeclaration - 1,
            );
        }

        return $namespace;
    }

    /** @see \PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\SelfMemberReferenceSniff::getDeclarationNameWithNamespace() */
    private function getDeclarationNameWithNamespace(array $tokens, $stackPtr): string
    {
        $nameParts = [];
        $currentPointer = $stackPtr;

        while ($tokens[$currentPointer]['code'] === T_NS_SEPARATOR
            || $tokens[$currentPointer]['code'] === T_STRING
            || isset(Tokens::$emptyTokens[$tokens[$currentPointer]['code']])
        ) {
            if (isset(Tokens::$emptyTokens[$tokens[$currentPointer]['code']])) {
                --$currentPointer;

                continue;
            }

            $nameParts[] = $tokens[$currentPointer]['content'];
            --$currentPointer;
        }

        $nameParts = array_reverse($nameParts);

        return implode('', $nameParts);
    }

    // Gets an array of docblock tag data, keyed by tag pointers with values of their end pointers.
    private function getDocblockTags(): array
    {
        $docblockOpenerPtr = $this->getDocblockOpenPtr();

        // If there is no docblock, return an empty array.
        if (!is_int($docblockOpenerPtr)) {
            return [];
        }

        $docblockOpener = $this->tokens[$docblockOpenerPtr];

        // Get the array of comment tag pointers.
        $tagPtrs = $docblockOpener['comment_tags'];

        if ($tagPtrs === []) {
            return [];
        }

        // Build an array of tag end pointers, using the next tag pointer of
        // each until the last tag, and then use the docblock end pointer.
        $nextTagsPtrs = array_slice($tagPtrs, 1);
        $nextTagsPtrs[] = $this->getDocblockCloserPtr();

        return array_combine($tagPtrs, $nextTagsPtrs);
    }

    private function getTagName($stackPtr): string
    {
        $actualTag = $this->tokens[$stackPtr];

        return $actualTag['content'];
    }

    private function getTagValue(int $tagPtr, int $tagEndPtr): string
    {
        $tagValuePtr = $this->phpcsFile->findNext(T_DOC_COMMENT_STRING, $tagPtr, $tagEndPtr);

        // Return an empty string if no value is found.
        if ($tagValuePtr === false) {
            return '';
        }

        return $this->tokens[$tagValuePtr]['content'];
    }

    private function formatErrorCode(string $errorCode, mixed $tagName): string
    {
        return sprintf($errorCode, ucfirst(ltrim((string) $tagName, '@')));
    }

    /** Removes a docblock tag and its value. */
    private function removeTag(int $currentTagPtr, int $tagEndPtr): void
    {
        $this->phpcsFile->fixer->beginChangeset();

        for ($tokenPtr = $currentTagPtr; $tokenPtr < $tagEndPtr; $tokenPtr++) {
            $this->phpcsFile->fixer->replaceToken($tokenPtr, '');
        }

        $this->phpcsFile->fixer->endChangeset();
    }

    private function addExpectedTag(string $expectedTagName, string $expectedTagValue): void
    {
        $docblockCloserPtr = $this->getDocblockCloserPtr();
        $this->phpcsFile->fixer->beginChangeset();
        $this->phpcsFile->fixer->addContentBefore($docblockCloserPtr, sprintf(
            '%s %s%s ',
            $expectedTagName,
            $expectedTagValue,
            PHP_EOL,
        ));
        $this->phpcsFile->fixer->endChangeset();
    }
}
