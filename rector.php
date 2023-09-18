<?php declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\MemoryCacheStorage;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

/** @see https://getrector.com/documentation/ */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->cacheClass(MemoryCacheStorage::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#php81
        SetList::CODE_QUALITY, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#codequality
        SetList::CODING_STYLE, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#codingstyle
        SetList::DEAD_CODE, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#deadcode
        SetList::EARLY_RETURN, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#earlyreturn
        SetList::PRIVATIZATION, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#privatization
        SetList::STRICT_BOOLEANS, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#strict
        SetList::TYPE_DECLARATION, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedeclaration
    ]);

    $rectorConfig->skip([
        CatchExceptionNameMatchingTypeRector::class => [__DIR__],
        ReadOnlyPropertyRector::class => [__DIR__ . '/src/Internal/Path/Value/Path.php'],
        EncapsedStringsToSprintfRector::class => [__DIR__],
        FinalizeClassesWithoutChildrenRector::class => [__DIR__], // This is duplicative of PHPCS sniff SlevomatCodingStandard.Classes.RequireAbstractOrFinal.
        LocallyCalledStaticMethodToNonStaticRector::class => [__DIR__],
        MixedTypeRector::class => [__DIR__],
        NewlineAfterStatementRector::class => [__DIR__],
        NewlineBeforeNewAssignSetRector::class => [__DIR__],
        NullToStrictStringFuncCallArgRector::class => [__DIR__ . '/tests/TestUtils/AssertTrait.php'],
    ]);
};
