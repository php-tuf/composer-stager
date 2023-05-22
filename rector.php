<?php declare(strict_types=1);

use Rector\Arguments\Rector\ClassMethod\ArgumentAdderRector;
use Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector;
use Rector\Arguments\Rector\FuncCall\FunctionArgumentDefaultValueReplacerRector;
use Rector\Arguments\Rector\FuncCall\SwapFuncCallArgumentsRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Caching\ValueObject\Storage\MemoryCacheStorage;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassConstFetch\ConvertStaticPrivateConstantToSelfRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\ClassMethod\NarrowUnionTypeDocRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Expression\TernaryFalseExpressionToIfRector;
use Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToArrayFilterRector;
use Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\AddPregQuoteDelimiterRector;
use Rector\CodeQuality\Rector\FuncCall\ArrayKeysAndInArrayToArrayKeyExistsRector;
use Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector;
use Rector\CodeQuality\Rector\FuncCall\BoolvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\CallUserFuncWithArrowFunctionToInlineRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FuncCall\FloatvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\InlineIsAInstanceOfRector;
use Rector\CodeQuality\Rector\FuncCall\IntvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\IsAWithStringWithThirdArgumentRector;
use Rector\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector;
use Rector\CodeQuality\Rector\FuncCall\SetTypeToCastRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FuncCall\StrvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\CodeQuality\Rector\FunctionLike\RemoveAlwaysTrueConditionSetInConstructorRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\GetClassToInstanceOfRector;
use Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector;
use Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Rector\CodeQuality\Rector\PropertyFetch\ExplicitMethodCallOverMagicGetSetRector;
use Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector;
use Rector\CodeQuality\Rector\Switch_\SwitchTrueToIfRector;
use Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;
use Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector;
use Rector\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Rector\CodingStyle\Rector\ClassConst\SplitGroupedClassConstantsRector;
use Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector;
use Rector\CodingStyle\Rector\ClassMethod\DataProviderArrayItemsNewlinedRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\ClassMethod\OrderAttributesRector;
use Rector\CodingStyle\Rector\ClassMethod\ReturnArrayClassMethodToYieldRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentImplodeRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\CodingStyle\Rector\If_\NullableCompareToNullRector;
use Rector\CodingStyle\Rector\MethodCall\PreferThisOrSelfMethodCallRector;
use Rector\CodingStyle\Rector\Plus\UseIncrementAssignRector;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\CodingStyle\Rector\Property\AddFalseDefaultToBoolPropertyRector;
use Rector\CodingStyle\Rector\Property\NullifyUnionNullableRector;
use Rector\CodingStyle\Rector\Property\SplitGroupedPropertiesRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector;
use Rector\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Compatibility\Rector\Class_\AttributeCompatibleAnnotationRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector;
use Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\BinaryOp\RemoveDuplicatedInstanceOfRector;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\Class_\TargetRemoveClassMethodRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassLike\RemoveAnnotationRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDelegatingParentCallRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveLastReturnRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector;
use Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Rector\DeadCode\Rector\Expression\SimplifyMirrorAssignRector;
use Rector\DeadCode\Rector\For_\RemoveDeadContinueRector;
use Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Rector\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector;
use Rector\DeadCode\Rector\FunctionLike\RemoveDuplicatedIfReturnRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Rector\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector;
use Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Rector\DeadCode\Rector\MethodCall\RemoveEmptyMethodCallRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveJustPropertyFetchForAssignRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveJustPropertyFetchRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveJustVariableAssignRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\DependencyInjection\Rector\Class_\ActionInjectionToConstructorInjectionRector;
use Rector\DependencyInjection\Rector\ClassMethod\AddMethodParentCallRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryAndToEarlyReturnRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Rector\Php82\Rector\New_\FilesystemIteratorSkipDotsRector;
use Rector\Privatization\Rector\Class_\ChangeGlobalVariablesToPropertiesRector;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\PSR4\Rector\FileWithoutNamespace\NormalizeNamespaceByPSR4ComposerAutoloadRector;
use Rector\PSR4\Rector\Namespace_\MultipleClassFileToPsr4ClassesRector;
use Rector\Removing\Rector\Class_\RemoveInterfacesRector;
use Rector\Removing\Rector\Class_\RemoveParentRector;
use Rector\Removing\Rector\Class_\RemoveTraitUseRector;
use Rector\Removing\Rector\ClassMethod\ArgumentRemoverRector;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallRector;
use Rector\RemovingStatic\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\ClassMethod\RenameAnnotationRector;
use Rector\Renaming\Rector\ConstFetch\RenameConstantRector;
use Rector\Renaming\Rector\FileWithoutNamespace\PseudoNamespaceToNamespaceRector;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\Namespace_\RenameNamespaceRector;
use Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector;
use Rector\Renaming\Rector\StaticCall\RenameStaticMethodRector;
use Rector\Renaming\Rector\String_\RenameStringRector;
use Rector\Restoration\Rector\Class_\RemoveFinalFromEntityRector;
use Rector\Restoration\Rector\ClassConstFetch\MissingClassConstantReferenceToStringRector;
use Rector\Restoration\Rector\ClassLike\UpdateFileNameByClassNameFileSystemRector;
use Rector\Restoration\Rector\Property\MakeTypedPropertyNullableIfCheckedRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Strict\Rector\BooleanNot\BooleanInBooleanNotRuleFixerRector;
use Rector\Strict\Rector\ClassMethod\AddConstructorParentCallRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
use Rector\Strict\Rector\Ternary\BooleanInTernaryOperatorRuleFixerRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\Transform\Rector\Assign\GetAndSetToMethodCallRector;
use Rector\Transform\Rector\Assign\PropertyAssignToMethodCallRector;
use Rector\Transform\Rector\Assign\PropertyFetchToMethodCallRector;
use Rector\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;
use Rector\Transform\Rector\Class_\AddAllowDynamicPropertiesAttributeRector;
use Rector\Transform\Rector\Class_\AddInterfaceByTraitRector;
use Rector\Transform\Rector\Class_\MergeInterfacesRector;
use Rector\Transform\Rector\Class_\ParentClassToTraitsRector;
use Rector\Transform\Rector\Class_\RemoveAllowDynamicPropertiesAttributeRector;
use Rector\Transform\Rector\ClassMethod\ReturnTypeWillChangeRector;
use Rector\Transform\Rector\ClassMethod\WrapReturnRector;
use Rector\Transform\Rector\FuncCall\FuncCallToConstFetchRector;
use Rector\Transform\Rector\FuncCall\FuncCallToMethodCallRector;
use Rector\Transform\Rector\FuncCall\FuncCallToNewRector;
use Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector;
use Rector\Transform\Rector\Isset_\UnsetAndIssetToMethodCallRector;
use Rector\Transform\Rector\MethodCall\MethodCallToFuncCallRector;
use Rector\Transform\Rector\MethodCall\MethodCallToMethodCallRector;
use Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector;
use Rector\Transform\Rector\MethodCall\MethodCallToStaticCallRector;
use Rector\Transform\Rector\MethodCall\ReplaceParentCallByPropertyCallRector;
use Rector\Transform\Rector\New_\NewArgToMethodCallRector;
use Rector\Transform\Rector\New_\NewToConstructorInjectionRector;
use Rector\Transform\Rector\New_\NewToMethodCallRector;
use Rector\Transform\Rector\New_\NewToStaticCallRector;
use Rector\Transform\Rector\StaticCall\StaticCallToFuncCallRector;
use Rector\Transform\Rector\StaticCall\StaticCallToMethodCallRector;
use Rector\Transform\Rector\StaticCall\StaticCallToNewRector;
use Rector\Transform\Rector\String_\StringToClassConstantRector;
use Rector\Transform\Rector\String_\ToStringToMethodCallRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector;
use Rector\TypeDeclaration\Rector\Class_\PropertyTypeFromStrictSetterGetterRector;
use Rector\TypeDeclaration\Rector\Class_\ReturnTypeFromStrictTernaryRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ArrayShapeFromConstantArrayReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\FalseReturnClassMethodToNullableRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamAnnotationIncorrectNullableRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnAnnotationIncorrectNullableRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRector;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictGetterMethodReturnTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\TypeDeclaration\Rector\Property\VarAnnotationIncorrectNullableRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\TypeDeclaration\Rector\Ternary\FlipNegatedTernaryInstanceofRector;
use Rector\TypeDeclaration\Rector\While_\WhileNullableToInstanceofRector;

/** @see https://getrector.com/documentation/ */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/phpcs',
        __DIR__ . '/phpstan',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->cacheClass(MemoryCacheStorage::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81, // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#php81
    ]);

    $rectorConfig->skip([
        MixedTypeRector::class,
    ]);

    // CodeQuality: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#codequality
    //$rectorConfig->rule(AbsolutizeRequireAndIncludePathRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#absolutizerequireandincludepathrector
    //$rectorConfig->rule(AddPregQuoteDelimiterRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addpregquotedelimiterrector
    //$rectorConfig->rule(AndAssignsToSeparateLinesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#andassignstoseparatelinesrector
    //$rectorConfig->rule(ArrayKeyExistsTernaryThenValueToCoalescingRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraykeyexiststernarythenvaluetocoalescingrector
    //$rectorConfig->rule(ArrayKeysAndInArrayToArrayKeyExistsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraykeysandinarraytoarraykeyexistsrector
    //$rectorConfig->rule(ArrayMergeOfNonArraysToSimpleArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraymergeofnonarraystosimplearrayrector
    //$rectorConfig->rule(BooleanNotIdenticalToNotIdenticalRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleannotidenticaltonotidenticalrector
    //$rectorConfig->rule(BoolvalToTypeCastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#boolvaltotypecastrector
    //$rectorConfig->rule(CallUserFuncWithArrowFunctionToInlineRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#calluserfuncwitharrowfunctiontoinlinerector
    //$rectorConfig->rule(CallableThisArrayToAnonymousFunctionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#callablethisarraytoanonymousfunctionrector
    //$rectorConfig->rule(ChangeArrayPushToArrayAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changearraypushtoarrayassignrector
    //$rectorConfig->rule(CombineIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#combineifrector
    //$rectorConfig->rule(CombinedAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#combinedassignrector
    //$rectorConfig->rule(CommonNotEqualRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#commonnotequalrector
    //$rectorConfig->rule(CompactToVariablesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#compacttovariablesrector
    //$rectorConfig->rule(CompleteDynamicPropertiesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#completedynamicpropertiesrector
    //$rectorConfig->rule(ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#consecutivenullcomparereturnstonullcoalescequeuerector
    //$rectorConfig->rule(ConvertStaticPrivateConstantToSelfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#convertstaticprivateconstanttoselfrector
    //$rectorConfig->rule(ExplicitBoolCompareRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#explicitboolcomparerector
    //$rectorConfig->rule(ExplicitMethodCallOverMagicGetSetRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#explicitmethodcallovermagicgetsetrector
    //$rectorConfig->rule(FlipTypeControlToUseExclusiveTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#fliptypecontroltouseexclusivetyperector
    //$rectorConfig->rule(FloatvalToTypeCastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#floatvaltotypecastrector
    //$rectorConfig->rule(ForRepeatedCountToOwnVariableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#forrepeatedcounttoownvariablerector
    //$rectorConfig->rule(ForeachItemsAssignToEmptyArrayToAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#foreachitemsassigntoemptyarraytoassignrector
    //$rectorConfig->rule(ForeachToInArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#foreachtoinarrayrector
    //$rectorConfig->rule(GetClassToInstanceOfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#getclasstoinstanceofrector
    //$rectorConfig->rule(InlineArrayReturnAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlinearrayreturnassignrector
    //$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlineconstructordefaulttopropertyrector
    //$rectorConfig->rule(InlineIfToExplicitIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlineiftoexplicitifrector
    //$rectorConfig->rule(InlineIsAInstanceOfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlineisainstanceofrector
    //$rectorConfig->rule(IntvalToTypeCastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#intvaltotypecastrector
    //$rectorConfig->rule(IsAWithStringWithThirdArgumentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#isawithstringwiththirdargumentrector
    //$rectorConfig->rule(IssetOnPropertyObjectToPropertyExistsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#issetonpropertyobjecttopropertyexistsrector
    //$rectorConfig->rule(JoinStringConcatRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#joinstringconcatrector
    //$rectorConfig->rule(LogicalToBooleanRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#logicaltobooleanrector
    //$rectorConfig->rule(NarrowUnionTypeDocRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#narrowuniontypedocrector
    //$rectorConfig->rule(NewStaticToNewSelfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newstatictonewselfrector
    //$rectorConfig->rule(OptionalParametersAfterRequiredRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#optionalparametersafterrequiredrector
    //$rectorConfig->rule(RemoveAlwaysTrueConditionSetInConstructorRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removealwaystrueconditionsetinconstructorrector
    //$rectorConfig->rule(RemoveSoleValueSprintfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removesolevaluesprintfrector
    //$rectorConfig->rule(ReplaceMultipleBooleanNotRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#replacemultiplebooleannotrector
    //$rectorConfig->rule(ReturnTypeFromStrictScalarReturnExprRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictscalarreturnexprrector
    //$rectorConfig->rule(SetTypeToCastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#settypetocastrector
    //$rectorConfig->rule(ShortenElseIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#shortenelseifrector
    //$rectorConfig->rule(SimplifyArraySearchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyarraysearchrector
    //$rectorConfig->rule(SimplifyBoolIdenticalTrueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyboolidenticaltruerector
    //$rectorConfig->rule(SimplifyConditionsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyconditionsrector
    //$rectorConfig->rule(SimplifyDeMorganBinaryRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifydemorganbinaryrector
    //$rectorConfig->rule(SimplifyEmptyArrayCheckRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyemptyarraycheckrector
    //$rectorConfig->rule(SimplifyEmptyCheckOnEmptyArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyemptycheckonemptyarrayrector
    //$rectorConfig->rule(SimplifyForeachToArrayFilterRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyforeachtoarrayfilterrector
    //$rectorConfig->rule(SimplifyForeachToCoalescingRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyforeachtocoalescingrector
    //$rectorConfig->rule(SimplifyFuncGetArgsCountRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyfuncgetargscountrector
    //$rectorConfig->rule(SimplifyIfElseToTernaryRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifelsetoternaryrector
    //$rectorConfig->rule(SimplifyIfNotNullReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifnotnullreturnrector
    //$rectorConfig->rule(SimplifyIfNullableReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifnullablereturnrector
    //$rectorConfig->rule(SimplifyIfReturnBoolRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifreturnboolrector
    //$rectorConfig->rule(SimplifyInArrayValuesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyinarrayvaluesrector
    //$rectorConfig->rule(SimplifyRegexPatternRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyregexpatternrector
    //$rectorConfig->rule(SimplifyStrposLowerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifystrposlowerrector
    //$rectorConfig->rule(SimplifyTautologyTernaryRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifytautologyternaryrector
    //$rectorConfig->rule(SimplifyUselessVariableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyuselessvariablerector
    //$rectorConfig->rule(SingleInArrayToCompareRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#singleinarraytocomparerector
    //$rectorConfig->rule(SingularSwitchToIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#singularswitchtoifrector
    //$rectorConfig->rule(StrlenZeroToIdenticalEmptyStringRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#strlenzerotoidenticalemptystringrector
    //$rectorConfig->rule(StrvalToTypeCastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#strvaltotypecastrector
    //$rectorConfig->rule(SwitchNegatedTernaryRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#switchnegatedternaryrector
    //$rectorConfig->rule(SwitchTrueToIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#switchtruetoifrector
    //$rectorConfig->rule(TernaryEmptyArrayArrayDimFetchToCoalesceRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#ternaryemptyarrayarraydimfetchtocoalescerector
    //$rectorConfig->rule(TernaryFalseExpressionToIfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#ternaryfalseexpressiontoifrector
    //$rectorConfig->rule(ThrowWithPreviousExceptionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#throwwithpreviousexceptionrector
    //$rectorConfig->rule(UnnecessaryTernaryExpressionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unnecessaryternaryexpressionrector
    //$rectorConfig->rule(UnusedForeachValueToArrayKeysRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unusedforeachvaluetoarraykeysrector
    //$rectorConfig->rule(UnwrapSprintfOneArgumentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unwrapsprintfoneargumentrector
    //$rectorConfig->rule(UseIdenticalOverEqualWithSameTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#useidenticaloverequalwithsametyperector

    // CodingStyle: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#codingstyle
    //$rectorConfig->rule(AddArrayDefaultToArrayPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addarraydefaulttoarraypropertyrector
    //$rectorConfig->rule(AddFalseDefaultToBoolPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addfalsedefaulttoboolpropertyrector
    //$rectorConfig->rule(BinarySwitchToIfElseRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#binaryswitchtoifelserector
    //$rectorConfig->rule(CallUserFuncArrayToVariadicRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#calluserfuncarraytovariadicrector
    //$rectorConfig->rule(CallUserFuncToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#calluserfunctomethodcallrector
    //$rectorConfig->rule(CatchExceptionNameMatchingTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#catchexceptionnamematchingtyperector
    //$rectorConfig->rule(ConsistentImplodeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#consistentimploderector
    //$rectorConfig->rule(CountArrayToEmptyArrayComparisonRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#countarraytoemptyarraycomparisonrector
    //$rectorConfig->rule(DataProviderArrayItemsNewlinedRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#dataproviderarrayitemsnewlinedrector
    //$rectorConfig->rule(EncapsedStringsToSprintfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#encapsedstringstosprintfrector
    //$rectorConfig->rule(FuncGetArgsToVariadicParamRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#funcgetargstovariadicparamrector
    //$rectorConfig->rule(MakeInheritedMethodVisibilitySameAsParentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#makeinheritedmethodvisibilitysameasparentrector
    //$rectorConfig->rule(NewlineAfterStatementRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newlineafterstatementrector
    //$rectorConfig->rule(NewlineBeforeNewAssignSetRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newlinebeforenewassignsetrector
    //$rectorConfig->rule(NullableCompareToNullRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#nullablecomparetonullrector
    //$rectorConfig->rule(NullifyUnionNullableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#nullifyunionnullablerector
    //$rectorConfig->rule(OrderAttributesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#orderattributesrector
    //$rectorConfig->rule(PostIncDecToPreIncDecRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#postincdectopreincdecrector
    //$rectorConfig->rule(PreferThisOrSelfMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#preferthisorselfmethodcallrector
    //$rectorConfig->rule(RemoveFinalFromConstRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removefinalfromconstrector
    //$rectorConfig->rule(ReturnArrayClassMethodToYieldRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnarrayclassmethodtoyieldrector
    //$rectorConfig->rule(SeparateMultiUseImportsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#separatemultiuseimportsrector
    //$rectorConfig->rule(SplitDoubleAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#splitdoubleassignrector
    //$rectorConfig->rule(SplitGroupedClassConstantsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#splitgroupedclassconstantsrector
    //$rectorConfig->rule(SplitGroupedPropertiesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#splitgroupedpropertiesrector
    //$rectorConfig->rule(StaticArrowFunctionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#staticarrowfunctionrector
    //$rectorConfig->rule(StaticClosureRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#staticclosurerector
    //$rectorConfig->rule(StrictArraySearchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#strictarraysearchrector
    //$rectorConfig->rule(SymplifyQuoteEscapeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#symplifyquoteescaperector
    //$rectorConfig->rule(TernaryConditionVariableAssignmentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#ternaryconditionvariableassignmentrector
    //$rectorConfig->rule(UnSpreadOperatorRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unspreadoperatorrector
    //$rectorConfig->rule(UseClassKeywordForClassNameResolutionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#useclasskeywordforclassnameresolutionrector
    //$rectorConfig->rule(UseIncrementAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#useincrementassignrector
    //$rectorConfig->rule(VarConstantCommentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#varconstantcommentrector
    //$rectorConfig->rule(VersionCompareFuncCallToConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#versioncomparefunccalltoconstantrector
    //$rectorConfig->rule(WrapEncapsedVariableInCurlyBracesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#wrapencapsedvariableincurlybracesrector

    // Compatibility: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#compatibility
    //$rectorConfig->rule(AttributeCompatibleAnnotationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#attributecompatibleannotationrector

    // DeadCode: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#deadcode
    //$rectorConfig->rule(RecastingRemovalRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#recastingremovalrector
    //$rectorConfig->rule(RemoveAlwaysTrueIfConditionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removealwaystrueifconditionrector
    //$rectorConfig->rule(RemoveAndTrueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeandtruerector
    //$rectorConfig->rule(RemoveAnnotationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeannotationrector
    //$rectorConfig->rule(RemoveConcatAutocastRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeconcatautocastrector
    //$rectorConfig->rule(RemoveDeadConditionAboveReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadconditionabovereturnrector
    //$rectorConfig->rule(RemoveDeadContinueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadcontinuerector
    //$rectorConfig->rule(RemoveDeadIfForeachForRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadifforeachforrector
    //$rectorConfig->rule(RemoveDeadInstanceOfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadinstanceofrector
    //$rectorConfig->rule(RemoveDeadLoopRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadlooprector
    //$rectorConfig->rule(RemoveDeadReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadreturnrector
    //$rectorConfig->rule(RemoveDeadStmtRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadstmtrector
    //$rectorConfig->rule(RemoveDeadTryCatchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadtrycatchrector
    //$rectorConfig->rule(RemoveDeadZeroAndOneOperationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedeadzeroandoneoperationrector
    //$rectorConfig->rule(RemoveDelegatingParentCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedelegatingparentcallrector
    //$rectorConfig->rule(RemoveDoubleAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removedoubleassignrector
    //$rectorConfig->rule(RemoveDuplicatedArrayKeyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeduplicatedarraykeyrector
    //$rectorConfig->rule(RemoveDuplicatedCaseInSwitchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeduplicatedcaseinswitchrector
    //$rectorConfig->rule(RemoveDuplicatedIfReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeduplicatedifreturnrector
    //$rectorConfig->rule(RemoveDuplicatedInstanceOfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeduplicatedinstanceofrector
    //$rectorConfig->rule(RemoveEmptyClassMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeemptyclassmethodrector
    //$rectorConfig->rule(RemoveEmptyMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeemptymethodcallrector
    //$rectorConfig->rule(RemoveJustPropertyFetchForAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removejustpropertyfetchforassignrector
    //$rectorConfig->rule(RemoveJustPropertyFetchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removejustpropertyfetchrector
    //$rectorConfig->rule(RemoveJustVariableAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removejustvariableassignrector
    //$rectorConfig->rule(RemoveLastReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removelastreturnrector
    //$rectorConfig->rule(RemoveNonExistingVarAnnotationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removenonexistingvarannotationrector
    //$rectorConfig->rule(RemoveNullPropertyInitializationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removenullpropertyinitializationrector
    //$rectorConfig->rule(RemoveParentCallWithoutParentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeparentcallwithoutparentrector
    //$rectorConfig->rule(RemovePhpVersionIdCheckRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removephpversionidcheckrector
    //$rectorConfig->rule(RemoveUnreachableStatementRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunreachablestatementrector
    //$rectorConfig->rule(RemoveUnusedConstructorParamRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedconstructorparamrector
    //$rectorConfig->rule(RemoveUnusedForeachKeyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedforeachkeyrector
    //$rectorConfig->rule(RemoveUnusedNonEmptyArrayBeforeForeachRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusednonemptyarraybeforeforeachrector
    //$rectorConfig->rule(RemoveUnusedPrivateClassConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedprivateclassconstantrector
    //$rectorConfig->rule(RemoveUnusedPrivateMethodParameterRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedprivatemethodparameterrector
    //$rectorConfig->rule(RemoveUnusedPrivateMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedprivatemethodrector
    //$rectorConfig->rule(RemoveUnusedPrivatePropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedprivatepropertyrector
    //$rectorConfig->rule(RemoveUnusedPromotedPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedpromotedpropertyrector
    //$rectorConfig->rule(RemoveUnusedVariableAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeunusedvariableassignrector
    //$rectorConfig->rule(RemoveUselessParamTagRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeuselessparamtagrector
    //$rectorConfig->rule(RemoveUselessReturnTagRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeuselessreturntagrector
    //$rectorConfig->rule(RemoveUselessVarTagRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeuselessvartagrector
    //$rectorConfig->rule(SimplifyIfElseWithSameContentRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifelsewithsamecontentrector
    //$rectorConfig->rule(SimplifyMirrorAssignRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifymirrorassignrector
    //$rectorConfig->rule(TargetRemoveClassMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#targetremoveclassmethodrector
    //$rectorConfig->rule(TernaryToBooleanOrFalseToBooleanAndRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#ternarytobooleanorfalsetobooleanandrector
    //$rectorConfig->rule(UnwrapFutureCompatibleIfPhpVersionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unwrapfuturecompatibleifphpversionrector

    // DependencyInjection: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#dependencyinjection
    //$rectorConfig->rule(ActionInjectionToConstructorInjectionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#actioninjectiontoconstructorinjectionrector
    //$rectorConfig->rule(AddMethodParentCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addmethodparentcallrector

    // EarlyReturn: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#earlyreturn
    //$rectorConfig->rule(ChangeAndIfToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changeandiftoearlyreturnrector
    //$rectorConfig->rule(ChangeIfElseValueAssignToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changeifelsevalueassigntoearlyreturnrector
    //$rectorConfig->rule(ChangeNestedForeachIfsToEarlyContinueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changenestedforeachifstoearlycontinuerector
    //$rectorConfig->rule(ChangeNestedIfsToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changenestedifstoearlyreturnrector
    //$rectorConfig->rule(ChangeOrIfContinueToMultiContinueRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changeorifcontinuetomulticontinuerector
    //$rectorConfig->rule(ChangeOrIfReturnToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changeorifreturntoearlyreturnrector
    //$rectorConfig->rule(PreparedValueToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#preparedvaluetoearlyreturnrector
    //$rectorConfig->rule(RemoveAlwaysElseRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removealwayselserector
    //$rectorConfig->rule(ReturnBinaryAndToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnbinaryandtoearlyreturnrector
    //$rectorConfig->rule(ReturnBinaryOrToEarlyReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnbinaryortoearlyreturnrector
    //$rectorConfig->rule(ReturnEarlyIfVariableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnearlyifvariablerector

    // Naming: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#naming
    //$rectorConfig->rule(RenameForeachValueVariableToMatchExprVariableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameforeachvaluevariabletomatchexprvariablerector
    //$rectorConfig->rule(RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameforeachvaluevariabletomatchmethodcallreturntyperector
    //$rectorConfig->rule(RenameParamToMatchTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameparamtomatchtyperector
    //$rectorConfig->rule(RenamePropertyToMatchTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamepropertytomatchtyperector
    //$rectorConfig->rule(RenameVariableToMatchMethodCallReturnTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamevariabletomatchmethodcallreturntyperector
    //$rectorConfig->rule(RenameVariableToMatchNewTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamevariabletomatchnewtyperector

    // PSR4: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#psr4
    //$rectorConfig->rule(MultipleClassFileToPsr4ClassesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#multipleclassfiletopsr4classesrector
    //$rectorConfig->rule(NormalizeNamespaceByPSR4ComposerAutoloadRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#normalizenamespacebypsr4composerautoloadrector

    // Privatization: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#privatization
    //$rectorConfig->rule(ChangeGlobalVariablesToPropertiesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changeglobalvariablestopropertiesrector
    //$rectorConfig->rule(ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changereadonlypropertywithdefaultvaluetoconstantrector
    //$rectorConfig->rule(ChangeReadOnlyVariableWithDefaultValueToConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changereadonlyvariablewithdefaultvaluetoconstantrector
    //$rectorConfig->rule(FinalizeClassesWithoutChildrenRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#finalizeclasseswithoutchildrenrector
    //$rectorConfig->rule(PrivatizeFinalClassMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#privatizefinalclassmethodrector
    //$rectorConfig->rule(PrivatizeFinalClassPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#privatizefinalclasspropertyrector
    //$rectorConfig->rule(PrivatizeLocalGetterToPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#privatizelocalgettertopropertyrector

    // RemovingStatic: https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removingstatic
    //$rectorConfig->rule(LocallyCalledStaticMethodToNonStaticRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#locallycalledstaticmethodtononstaticrector
    //$rectorConfig->rule(PseudoNamespaceToNamespaceRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#pseudonamespacetonamespacerector
    //$rectorConfig->rule(RenameAnnotationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameannotationrector
    //$rectorConfig->rule(RenameClassConstFetchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameclassconstfetchrector
    //$rectorConfig->rule(RenameClassRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameclassrector
    //$rectorConfig->rule(RenameConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renameconstantrector
    //$rectorConfig->rule(RenameFunctionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamefunctionrector
    //$rectorConfig->rule(RenameMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamemethodrector
    //$rectorConfig->rule(RenameNamespaceRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamenamespacerector
    //$rectorConfig->rule(RenamePropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamepropertyrector
    //$rectorConfig->rule(RenameStaticMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamestaticmethodrector
    //$rectorConfig->rule(RenameStringRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#renamestringrector
    //$rectorConfig->rule(MakeTypedPropertyNullableIfCheckedRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#maketypedpropertynullableifcheckedrector
    //$rectorConfig->rule(MissingClassConstantReferenceToStringRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#missingclassconstantreferencetostringrector
    //$rectorConfig->rule(RemoveFinalFromEntityRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removefinalfromentityrector
    //$rectorConfig->rule(UpdateFileNameByClassNameFileSystemRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#updatefilenamebyclassnamefilesystemrector
    //$rectorConfig->rule(AddConstructorParentCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addconstructorparentcallrector
    //$rectorConfig->rule(BooleanInBooleanNotRuleFixerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleaninbooleannotrulefixerrector
    //$rectorConfig->rule(BooleanInIfConditionRuleFixerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleaninifconditionrulefixerrector
    //$rectorConfig->rule(BooleanInTernaryOperatorRuleFixerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleaninternaryoperatorrulefixerrector
    //$rectorConfig->rule(DisallowedEmptyRuleFixerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#disallowedemptyrulefixerrector
    //$rectorConfig->rule(DisallowedShortTernaryRuleFixerRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#disallowedshortternaryrulefixerrector
    //$rectorConfig->rule(AddAllowDynamicPropertiesAttributeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addallowdynamicpropertiesattributerector
    //$rectorConfig->rule(AddInterfaceByTraitRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addinterfacebytraitrector
    //$rectorConfig->rule(AttributeKeyToClassConstFetchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#attributekeytoclassconstfetchrector
    //$rectorConfig->rule(FuncCallToConstFetchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#funccalltoconstfetchrector
    //$rectorConfig->rule(FuncCallToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#funccalltomethodcallrector
    //$rectorConfig->rule(FuncCallToNewRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#funccalltonewrector
    //$rectorConfig->rule(FuncCallToStaticCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#funccalltostaticcallrector
    //$rectorConfig->rule(GetAndSetToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#getandsettomethodcallrector
    //$rectorConfig->rule(MergeInterfacesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#mergeinterfacesrector
    //$rectorConfig->rule(MethodCallToFuncCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#methodcalltofunccallrector
    //$rectorConfig->rule(MethodCallToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#methodcalltomethodcallrector
    //$rectorConfig->rule(MethodCallToPropertyFetchRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#methodcalltopropertyfetchrector
    //$rectorConfig->rule(MethodCallToStaticCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#methodcalltostaticcallrector
    //$rectorConfig->rule(NewArgToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newargtomethodcallrector
    //$rectorConfig->rule(NewToConstructorInjectionRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newtoconstructorinjectionrector
    //$rectorConfig->rule(NewToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newtomethodcallrector
    //$rectorConfig->rule(NewToStaticCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#newtostaticcallrector
    //$rectorConfig->rule(ParentClassToTraitsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#parentclasstotraitsrector
    //$rectorConfig->rule(PropertyAssignToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#propertyassigntomethodcallrector
    //$rectorConfig->rule(PropertyFetchToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#propertyfetchtomethodcallrector
    //$rectorConfig->rule(RemoveAllowDynamicPropertiesAttributeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removeallowdynamicpropertiesattributerector
    //$rectorConfig->rule(ReplaceParentCallByPropertyCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#replaceparentcallbypropertycallrector
    //$rectorConfig->rule(ReturnTypeWillChangeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypewillchangerector
    //$rectorConfig->rule(StaticCallToFuncCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#staticcalltofunccallrector
    //$rectorConfig->rule(StaticCallToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#staticcalltomethodcallrector
    //$rectorConfig->rule(StaticCallToNewRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#staticcalltonewrector
    //$rectorConfig->rule(StringToClassConstantRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#stringtoclassconstantrector
    //$rectorConfig->rule(ToStringToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#tostringtomethodcallrector
    //$rectorConfig->rule(UnsetAndIssetToMethodCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unsetandissettomethodcallrector
    //$rectorConfig->rule(WrapReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#wrapreturnrector
    //$rectorConfig->rule(AddArrowFunctionReturnTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addarrowfunctionreturntyperector
    //$rectorConfig->rule(AddClosureReturnTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addclosurereturntyperector
    //$rectorConfig->rule(AddMethodCallBasedStrictParamTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addmethodcallbasedstrictparamtyperector
    //$rectorConfig->rule(AddParamTypeBasedOnPHPUnitDataProviderRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addparamtypebasedonphpunitdataproviderrector
    //$rectorConfig->rule(AddParamTypeDeclarationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addparamtypedeclarationrector
    //$rectorConfig->rule(AddParamTypeFromPropertyTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addparamtypefrompropertytyperector
    //$rectorConfig->rule(AddParamTypeSplFixedArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addparamtypesplfixedarrayrector
    //$rectorConfig->rule(AddPropertyTypeDeclarationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addpropertytypedeclarationrector
    //$rectorConfig->rule(AddReturnTypeDeclarationBasedOnParentClassMethodRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addreturntypedeclarationbasedonparentclassmethodrector
    //$rectorConfig->rule(AddReturnTypeDeclarationFromYieldsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addreturntypedeclarationfromyieldsrector
    //$rectorConfig->rule(AddReturnTypeDeclarationRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addreturntypedeclarationrector
    //$rectorConfig->rule(AddVoidReturnTypeWhereNoReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addvoidreturntypewherenoreturnrector
    //$rectorConfig->rule(ArrayShapeFromConstantArrayReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arrayshapefromconstantarrayreturnrector
    //$rectorConfig->rule(BinaryOpNullableToInstanceofRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#binaryopnullabletoinstanceofrector
    //$rectorConfig->rule(DeclareStrictTypesRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#declarestricttypesrector
    //$rectorConfig->rule(EmptyOnNullableObjectToInstanceOfRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#emptyonnullableobjecttoinstanceofrector
    //$rectorConfig->rule(FalseReturnClassMethodToNullableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#falsereturnclassmethodtonullablerector
    //$rectorConfig->rule(FlipNegatedTernaryInstanceofRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#flipnegatedternaryinstanceofrector
    //$rectorConfig->rule(ParamAnnotationIncorrectNullableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#paramannotationincorrectnullablerector
    //$rectorConfig->rule(ParamTypeByMethodCallTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#paramtypebymethodcalltyperector
    //$rectorConfig->rule(ParamTypeByParentCallTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#paramtypebyparentcalltyperector
    //$rectorConfig->rule(ParamTypeFromStrictTypedPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#paramtypefromstricttypedpropertyrector
    //$rectorConfig->rule(PropertyTypeFromStrictSetterGetterRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#propertytypefromstrictsettergetterrector
    //$rectorConfig->rule(ReturnAnnotationIncorrectNullableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnannotationincorrectnullablerector
    //$rectorConfig->rule(ReturnNeverTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returnnevertyperector
    //$rectorConfig->rule(ReturnTypeFromReturnDirectArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromreturndirectarrayrector
    //$rectorConfig->rule(ReturnTypeFromReturnNewRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromreturnnewrector
    //$rectorConfig->rule(ReturnTypeFromStrictBoolReturnExprRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictboolreturnexprrector
    //$rectorConfig->rule(ReturnTypeFromStrictConstantReturnRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictconstantreturnrector
    //$rectorConfig->rule(ReturnTypeFromStrictNativeCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictnativecallrector
    //$rectorConfig->rule(ReturnTypeFromStrictNewArrayRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictnewarrayrector
    //$rectorConfig->rule(ReturnTypeFromStrictTernaryRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstrictternaryrector
    //$rectorConfig->rule(ReturnTypeFromStrictTypedCallRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstricttypedcallrector
    //$rectorConfig->rule(ReturnTypeFromStrictTypedPropertyRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromstricttypedpropertyrector
    //$rectorConfig->rule(TypedPropertyFromAssignsRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedpropertyfromassignsrector
    //$rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedpropertyfromstrictconstructorrector
    //$rectorConfig->rule(TypedPropertyFromStrictGetterMethodReturnTypeRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedpropertyfromstrictgettermethodreturntyperector
    //$rectorConfig->rule(TypedPropertyFromStrictSetUpRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedpropertyfromstrictsetuprector
    //$rectorConfig->rule(VarAnnotationIncorrectNullableRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#varannotationincorrectnullablerector
    //$rectorConfig->rule(WhileNullableToInstanceofRector::class); // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#whilenullabletoinstanceofrector};
};