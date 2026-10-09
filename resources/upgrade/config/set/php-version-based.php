<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\Rules\Arguments\Rector\ClassMethod\ArgumentAdderRector;
use Flames\Code\Upgrade\Rules\Arguments\Rector\FuncCall\FunctionArgumentDefaultValueReplacerRector;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ArgumentAdder;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ReplaceFuncCallArgumentDefaultValue;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\ClosureFromCallableToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\ConsistentImplodeRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Flames\Code\Upgrade\Rules\Php52\Rector\Property\VarToPublicPropertyRector;
use Flames\Code\Upgrade\Rules\Php52\Rector\Switch_\ContinueToBreakInSwitchRector;
use Flames\Code\Upgrade\Rules\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRector;
use Flames\Code\Upgrade\Rules\Php53\Rector\Ternary\TernaryToElvisRector;
use Flames\Code\Upgrade\Rules\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector;
use Flames\Code\Upgrade\Rules\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Flames\Code\Upgrade\Rules\Php54\Rector\Break_\RemoveZeroBreakContinueRector;
use Flames\Code\Upgrade\Rules\Php54\Rector\FuncCall\RemoveReferenceFromCallRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\ClassConstFetch\StaticToSelfOnFinalClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\GetCalledClassToSelfClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\GetCalledClassToStaticClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\PregReplaceEModifierRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Flames\Code\Upgrade\Rules\Php56\Rector\FuncCall\PowToExpRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Assign\ListSplitStringRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Break_\BreakNotInLoopOrSwitchToReturnRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\ClassMethod\Php4ConstructorRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\CallUserMethodRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\EregToPregMatchRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\MultiDirnameRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\RandomFunctionRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\RenameMktimeWithoutArgsToTimeRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\FunctionLike\ExceptionHandlerTypehintRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\If_\IfToSpaceshipRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\List_\EmptyListRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Switch_\ReduceMultipleDefaultSwitchRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Ternary\TernaryToNullCoalescingRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Ternary\TernaryToSpaceshipRector;
use Flames\Code\Upgrade\Rules\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\Assign\AssignArrayToStringRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\BinaryOp\BinaryOpBetweenNumberAndStringRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\BooleanOr\IsIterableRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\List_\ListToArrayDestructRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\TryCatch\MultiExceptionCatchRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\Assign\ListEachRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\Assign\ReplaceEachAssignmentWithKeyCurrentRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\GetClassOnNullRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\StringifyDefineRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\StringsAssertNakedRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\Unset_\UnsetCastRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\While_\WhileEachToForeachRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\BooleanOr\IsCountableRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\ArrayKeysToArrayKeyFirstLastRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\RegexDashEscapeRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\SensitiveDefineRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\SetCookieRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\String_\SensitiveHereNowDocRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\ArrayDimFetch\CurlyToSquareBracketArrayStringRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\FilterVarToAddSlashesRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\HebrevcToNl2brHebrevRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\MbStrrposEncodingArgumentPositionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\MoneyFormatToNumberFormatRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\RestoreIncludePathToIniRestoreRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\If_\IfToNullCoalescingAssignRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\StaticCall\ExportToReflectionFunctionRector;
use Flames\Code\Upgrade\Rules\Php74\Rector\Ternary\ParenthesizeNestedTernaryRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Class_\StringableForToStringRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\ClassConstFetch\ClassOnThisVariableObjectRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\ClassMethod\AddParamBasedOnParentClassMethodRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\ClassMethod\FinalPrivateToPrivateVisibilityRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\ClassMethod\SetStateToStaticRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\FuncCall\ClassOnObjectRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Identical\StrEndsWithRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Identical\StrStartsWithRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\NotIdentical\StrContainsRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Ternary\GetDebugTypeRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Ternary\TernaryToNullsafeCoalesceRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Class_\MyCLabsClassToEnumRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Class_\SpatieEnumClassToEnumRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\FuncCall\NullToStrictIntPregSlitFuncCallLimitArgRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\MyCLabsMethodCallToEnumConstRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\SpatieEnumMethodCallToEnumConstRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\New_\MyCLabsConstructorCallToEnumFromRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Property\ReadOnlyPropertyRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\Class_\ReadOnlyClassRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\New_\FilesystemIteratorSkipDotsRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\BooleanAnd\JsonValidateRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\Class_\ReadOnlyAnonymousClassRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\ClassConst\AddTypeToConstRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\CombineHostPortLdapUriRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\DynamicClassConstFetchRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\RemoveGetClassGetParentClassNoArgsRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindKeyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall\AddEscapeArgumentRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall\RoundingModeEnumRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\ArrayDimFetch\ArrayFirstLastRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Class_\SleepToSerializeRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Class_\WakeupToUnserializeRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\ClassMethod\NullDebugInfoReturnRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\ArrayKeyExistsNullToEmptyStringRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\ChrArgModuloRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\OrdSingleByteRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\RemoveFinfoBufferContextArgRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\ShellExec\ShellExecFunctionCallOverBackticksRector;
use Flames\Code\Upgrade\Rules\Php85\Rector\Switch_\ColonAfterSwitchCaseRector;
use Flames\Code\Upgrade\Rules\Php86\Rector\Class_\ConstructorReadonlyAssignToDefaultRector;
use Flames\Code\Upgrade\Rules\Php86\Rector\FuncCall\MinMaxToClampRector;
use Flames\Code\Upgrade\Rules\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Flames\Code\Upgrade\Rules\Removing\Rector\FuncCall\RemoveFuncCallRector;
use Flames\Code\Upgrade\Rules\Removing\ValueObject\RemoveFuncCallArg;
use Flames\Code\Upgrade\Rules\Renaming\Rector\Cast\RenameCastRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\ConstFetch\RenameConstantRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\MethodCall\RenameMethodRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\MethodCallRename;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameCast;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameClassAndConstFetch;
use Flames\Code\Upgrade\Rules\Transform\Rector\StaticCall\StaticCallToFuncCallRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\StaticCallToFuncCall;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\Rules\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRector;
// all PHP version rules, oldest to newest; each rule gates itself at runtime by PHP version,
// so only rules up to the target PHP version apply
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        // PHP 5.2
        VarToPublicPropertyRector::class,
        ContinueToBreakInSwitchRector::class,
        // PHP 5.3
        ExplicitPublicClassMethodRector::class,
        TernaryToElvisRector::class,
        DirNameFileConstantToDirConstantRector::class,
        ReplaceHttpServerVarsByServerRector::class,
        // PHP 5.4
        LongArrayToShortArrayRector::class,
        RemoveReferenceFromCallRector::class,
        RemoveZeroBreakContinueRector::class,
        // PHP 5.5
        StringClassNameToClassConstantRector::class,
        ClassConstantToSelfClassRector::class,
        PregReplaceEModifierRector::class,
        GetCalledClassToSelfClassRector::class,
        GetCalledClassToStaticClassRector::class,
        StaticToSelfOnFinalClassRector::class,
        // PHP 5.6
        PowToExpRector::class,
        // PHP 7.0
        Php4ConstructorRector::class,
        TernaryToNullCoalescingRector::class,
        RandomFunctionRector::class,
        ExceptionHandlerTypehintRector::class,
        MultiDirnameRector::class,
        ListSplitStringRector::class,
        EmptyListRector::class,
        CallUserMethodRector::class,
        EregToPregMatchRector::class,
        ReduceMultipleDefaultSwitchRector::class,
        TernaryToSpaceshipRector::class,
        WrapVariableVariableNameInCurlyBracesRector::class,
        IfToSpaceshipRector::class,
        ThisCallOnStaticMethodToStaticCallRector::class,
        BreakNotInLoopOrSwitchToReturnRector::class,
        RenameMktimeWithoutArgsToTimeRector::class,
        IfIssetToCoalescingRector::class,
        // PHP 7.1
        IsIterableRector::class,
        MultiExceptionCatchRector::class,
        AssignArrayToStringRector::class,
        RemoveExtraParametersRector::class,
        BinaryOpBetweenNumberAndStringRector::class,
        ListToArrayDestructRector::class,
        // PHP 7.2
        GetClassOnNullRector::class,
        ParseStrWithResultArgumentRector::class,
        StringsAssertNakedRector::class,
        CreateFunctionToAnonymousFunctionRector::class,
        StringifyDefineRector::class,
        WhileEachToForeachRector::class,
        ListEachRector::class,
        ReplaceEachAssignmentWithKeyCurrentRector::class,
        UnsetCastRector::class,
        // PHP 7.3
        StringifyStrNeedlesRector::class,
        RegexDashEscapeRector::class,
        SetCookieRector::class,
        IsCountableRector::class,
        ArrayKeyFirstLastRector::class,
        ArrayKeysToArrayKeyFirstLastRector::class,
        SensitiveDefineRector::class,
        SensitiveConstantNameRector::class,
        SensitiveHereNowDocRector::class,
        // PHP 7.4
        ArrayKeyExistsOnPropertyRector::class,
        FilterVarToAddSlashesRector::class,
        ExportToReflectionFunctionRector::class,
        MbStrrposEncodingArgumentPositionRector::class,
        NullCoalescingOperatorRector::class,
        IfToNullCoalescingAssignRector::class,
        ClosureToArrowFunctionRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        CurlyToSquareBracketArrayStringRector::class,
        MoneyFormatToNumberFormatRector::class,
        ParenthesizeNestedTernaryRector::class,
        RestoreIncludePathToIniRestoreRector::class,
        HebrevcToNl2brHebrevRector::class,
        // PHP 8.0
        StrContainsRector::class,
        StrStartsWithRector::class,
        StrEndsWithRector::class,
        StringableForToStringRector::class,
        ClassOnObjectRector::class,
        GetDebugTypeRector::class,
        TernaryToNullsafeCoalesceRector::class,
        RemoveUnusedVariableInCatchRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ChangeSwitchToMatchRector::class,
        RemoveParentCallWithoutParentRector::class,
        SetStateToStaticRector::class,
        FinalPrivateToPrivateVisibilityRector::class,
        AddParamBasedOnParentClassMethodRector::class,
        ClassOnThisVariableObjectRector::class,
        ConsistentImplodeRector::class,
        OptionalParametersAfterRequiredRector::class,
        // PHP 8.1
        ReturnNeverTypeRector::class,
        MyCLabsClassToEnumRector::class,
        MyCLabsMethodCallToEnumConstRector::class,
        MyCLabsConstructorCallToEnumFromRector::class,
        ReadOnlyPropertyRector::class,
        SpatieEnumClassToEnumRector::class,
        SpatieEnumMethodCallToEnumConstRector::class,
        NullToStrictIntPregSlitFuncCallLimitArgRector::class,
        ArrayToFirstClassCallableRector::class,
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
        ClosureDelegatingCallToFirstClassCallableRector::class,
        ClosureFromCallableToFirstClassCallableRector::class,
        FunctionFirstClassCallableRector::class,
        RemoveReflectionSetAccessibleCallsRector::class,
        // PHP 8.2
        ReadOnlyClassRector::class,
        Utf8DecodeEncodeToMbConvertEncodingRector::class,
        FilesystemIteratorSkipDotsRector::class,
        VariableInStringInterpolationFixerRector::class,
        // PHP 8.3
        AddTypeToConstRector::class,
        CombineHostPortLdapUriRector::class,
        RemoveGetClassGetParentClassNoArgsRector::class,
        ReadOnlyAnonymousClassRector::class,
        DynamicClassConstFetchRector::class,
        JsonValidateRector::class,
        // PHP 8.4
        ExplicitNullableParamTypeRector::class,
        RoundingModeEnumRector::class,
        AddEscapeArgumentRector::class,
        NewMethodCallWithoutParenthesesRector::class,
        ForeachToArrayFindRector::class,
        ForeachToArrayFindKeyRector::class,
        ForeachToArrayAllRector::class,
        ForeachToArrayAnyRector::class,
        // PHP 8.5
        ArrayFirstLastRector::class,
        RemoveFinfoBufferContextArgRector::class,
        NullDebugInfoReturnRector::class,
        ColonAfterSwitchCaseRector::class,
        ArrayKeyExistsNullToEmptyStringRector::class,
        ChrArgModuloRector::class,
        SleepToSerializeRector::class,
        OrdSingleByteRector::class,
        WakeupToUnserializeRector::class,
        ShellExecFunctionCallOverBackticksRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        // PHP 8.6
        MinMaxToClampRector::class,
        ConstructorReadonlyAssignToDefaultRector::class,
    ]);
    // configured rules, each bound to the PHP version its configuration targets
    // PHP 5.2
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RemoveFuncCallArgRector::class, [
        // see https://www.php.net/manual/en/function.ldap-first-attribute.php
        new RemoveFuncCallArg('ldap_first_attribute', 2),
    ], PhpVersion::PHP_52);
    // PHP 5.4
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, ['mysqli_param_count' => 'mysqli_stmt_param_count'], PhpVersion::PHP_54);
    // PHP 5.6
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, ['mcrypt_generic_end' => 'mcrypt_generic_deinit', 'set_socket_blocking' => 'stream_set_blocking', 'ocibindbyname' => 'oci_bind_by_name', 'ocicancel' => 'oci_cancel', 'ocicolumnisnull' => 'oci_field_is_null', 'ocicolumnname' => 'oci_field_name', 'ocicolumnprecision' => 'oci_field_precision', 'ocicolumnscale' => 'oci_field_scale', 'ocicolumnsize' => 'oci_field_size', 'ocicolumntype' => 'oci_field_type', 'ocicolumntyperaw' => 'oci_field_type_raw', 'ocicommit' => 'oci_commit', 'ocidefinebyname' => 'oci_define_by_name', 'ocierror' => 'oci_error', 'ociexecute' => 'oci_execute', 'ocifetch' => 'oci_fetch', 'ocifetchstatement' => 'oci_fetch_all', 'ocifreecursor' => 'oci_free_statement', 'ocifreestatement' => 'oci_free_statement', 'ociinternaldebug' => 'oci_internal_debug', 'ocilogoff' => 'oci_close', 'ocilogon' => 'oci_connect', 'ocinewcollection' => 'oci_new_collection', 'ocinewcursor' => 'oci_new_cursor', 'ocinewdescriptor' => 'oci_new_descriptor', 'ocinlogon' => 'oci_new_connect', 'ocinumcols' => 'oci_num_fields', 'ociparse' => 'oci_parse', 'ociplogon' => 'oci_pconnect', 'ociresult' => 'oci_result', 'ocirollback' => 'oci_rollback', 'ocirowcount' => 'oci_num_rows', 'ociserverversion' => 'oci_server_version', 'ocisetprefetch' => 'oci_set_prefetch', 'ocistatementtype' => 'oci_statement_type'], PhpVersion::PHP_56);
    // PHP 7.2
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, [
        # and imagewbmp
        'jpeg2wbmp' => 'imagecreatefromjpeg',
        # or imagewbmp
        'png2wbmp' => 'imagecreatefrompng',
        # migration72.deprecated.gmp_random-function
        # http://php.net/manual/en/migration72.deprecated.php
        # or gmp_random_range
        'gmp_random' => 'gmp_random_bits',
        'read_exif_data' => 'exif_read_data',
    ], PhpVersion::PHP_72);
    // PHP 7.3
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, [
        # https://wiki.php.net/rfc/deprecations_php_7_3
        'image2wbmp' => 'imagewbmp',
        'mbregex_encoding' => 'mb_regex_encoding',
        'mbereg' => 'mb_ereg',
        'mberegi' => 'mb_eregi',
        'mbereg_replace' => 'mb_ereg_replace',
        'mberegi_replace' => 'mb_eregi_replace',
        'mbsplit' => 'mb_split',
        'mbereg_match' => 'mb_ereg_match',
        'mbereg_search' => 'mb_ereg_search',
        'mbereg_search_pos' => 'mb_ereg_search_pos',
        'mbereg_search_regs' => 'mb_ereg_search_regs',
        'mbereg_search_init' => 'mb_ereg_search_init',
        'mbereg_search_getregs' => 'mb_ereg_search_getregs',
        'mbereg_search_getpos' => 'mb_ereg_search_getpos',
    ], PhpVersion::PHP_73);
    // PHP 7.4
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, [
        # the_real_type
        # https://wiki.php.net/rfc/deprecations_php_7_4
        'is_real' => 'is_float',
    ], PhpVersion::PHP_74);
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameCastRector::class, [new RenameCast(Double::class, Double::KIND_REAL, Double::KIND_FLOAT)], PhpVersion::PHP_74);
    // PHP 8.0
    $rectorConfig->ruleWithConfigurationPhpVersionBound(StaticCallToFuncCallRector::class, [new StaticCallToFuncCall('Nette\Utils\Strings', 'startsWith', 'str_starts_with'), new StaticCallToFuncCall('Nette\Utils\Strings', 'endsWith', 'str_ends_with'), new StaticCallToFuncCall('Nette\Utils\Strings', 'contains', 'str_contains')], PhpVersion::PHP_80);
    // nette\utils and Strings::replace()
    $rectorConfig->ruleWithConfigurationPhpVersionBound(ArgumentAdderRector::class, [new ArgumentAdder('Nette\Utils\Strings', 'replace', 2, 'replacement', '')], PhpVersion::PHP_80);
    // @see https://php.watch/versions/8.0/pgsql-aliases-deprecated
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, ['pg_clientencoding' => 'pg_client_encoding', 'pg_cmdtuples' => 'pg_affected_rows', 'pg_errormessage' => 'pg_last_error', 'pg_fieldisnull' => 'pg_field_is_null', 'pg_fieldname' => 'pg_field_name', 'pg_fieldnum' => 'pg_field_num', 'pg_fieldprtlen' => 'pg_field_prtlen', 'pg_fieldsize' => 'pg_field_size', 'pg_fieldtype' => 'pg_field_type', 'pg_freeresult' => 'pg_free_result', 'pg_getlastoid' => 'pg_last_oid', 'pg_loclose' => 'pg_lo_close', 'pg_locreate' => 'pg_lo_create', 'pg_loexport' => 'pg_lo_export', 'pg_loimport' => 'pg_lo_import', 'pg_loopen' => 'pg_lo_open', 'pg_loread' => 'pg_lo_read', 'pg_loreadall' => 'pg_lo_read_all', 'pg_lounlink' => 'pg_lo_unlink', 'pg_lowrite' => 'pg_lo_write', 'pg_numfields' => 'pg_num_fields', 'pg_numrows' => 'pg_num_rows', 'pg_result' => 'pg_fetch_result', 'pg_setclientencoding' => 'pg_set_client_encoding'], PhpVersion::PHP_80);
    $rectorConfig->ruleWithConfigurationPhpVersionBound(FunctionArgumentDefaultValueReplacerRector::class, [new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'gte', 'ge'), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'lte', 'le'), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, '', '!='), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, '!', '!='), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'g', 'gt'), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'l', 'lt'), new ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'n', 'ne'), new ReplaceFuncCallArgumentDefaultValue('get_headers', 1, 0, \false), new ReplaceFuncCallArgumentDefaultValue('get_headers', 1, 1, \true)], PhpVersion::PHP_80);
    // PHP 8.5
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RemoveFuncCallArgRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_key_length_parameter_of_openssl_pkey_derive
        new RemoveFuncCallArg('openssl_pkey_derive', 2),
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_the_exclude_disabled_parameter_of_get_defined_functions
        new RemoveFuncCallArg('get_defined_functions', 0),
    ], PhpVersion::PHP_85);
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameMethodRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_splobjectstoragecontains_splobjectstorageattach_and_splobjectstoragedetach
        new MethodCallRename('SplObjectStorage', 'contains', 'offsetExists'),
        new MethodCallRename('SplObjectStorage', 'attach', 'offsetSet'),
        new MethodCallRename('SplObjectStorage', 'detach', 'offsetUnset'),
        // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_driver_specific_pdo_constants_and_methods
        new MethodCallRename('PDO', 'pgsqlCopyFromArray', 'copyFromArray'),
        new MethodCallRename('PDO', 'pgsqlCopyFromFile', 'copyFromFile'),
        new MethodCallRename('PDO', 'pgsqlCopyToArray', 'copyToArray'),
        new MethodCallRename('PDO', 'pgsqlCopyToFile', 'copyToFile'),
        new MethodCallRename('PDO', 'pgsqlGetNotify', 'getNotify'),
        new MethodCallRename('PDO', 'pgsqlGetPid', 'getPid'),
        new MethodCallRename('PDO', 'pgsqlLOBCreate', 'lobCreate'),
        new MethodCallRename('PDO', 'pgsqlLOBOpen', 'lobOpen'),
        new MethodCallRename('PDO', 'pgsqlLOBUnlink', 'lobUnlink'),
        new MethodCallRename('PDO', 'sqliteCreateAggregate', 'createAggregate'),
        new MethodCallRename('PDO', 'sqliteCreateCollation', 'createCollation'),
        new MethodCallRename('PDO', 'sqliteCreateFunction', 'createFunction'),
    ], PhpVersion::PHP_85);
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameFunctionRector::class, [
        // https://wiki.php.net/rfc/deprecations_php_8_5#formally_deprecate_socket_set_timeout
        'socket_set_timeout' => 'stream_set_timeout',
        // https://wiki.php.net/rfc/deprecations_php_8_5#formally_deprecate_mysqli_execute
        'mysqli_execute' => 'mysqli_stmt_execute',
    ], PhpVersion::PHP_85);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_driver_specific_pdo_constants_and_methods
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameClassConstFetchRector::class, [new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_CONNECTION_TIMEOUT', \Pdo\Dblib::class, 'ATTR_CONNECTION_TIMEOUT'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_QUERY_TIMEOUT', \Pdo\Dblib::class, 'ATTR_QUERY_TIMEOUT'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER', \Pdo\Dblib::class, 'ATTR_STRINGIFY_UNIQUEIDENTIFIER'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_VERSION', \Pdo\Dblib::class, 'ATTR_VERSION'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_TDS_VERSION', \Pdo\Dblib::class, 'ATTR_TDS_VERSION'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_SKIP_EMPTY_ROWSETS', \Pdo\Dblib::class, 'ATTR_SKIP_EMPTY_ROWSETS'), new RenameClassAndConstFetch('PDO', 'DBLIB_ATTR_DATETIME_CONVERT', \Pdo\Dblib::class, 'ATTR_DATETIME_CONVERT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_DATE_FORMAT', \Pdo\Firebird::class, 'ATTR_DATE_FORMAT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_TIME_FORMAT', \Pdo\Firebird::class, 'ATTR_TIME_FORMAT'), new RenameClassAndConstFetch('PDO', 'FB_ATTR_TIMESTAMP_FORMAT', \Pdo\Firebird::class, 'ATTR_TIMESTAMP_FORMAT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_USE_BUFFERED_QUERY', \Pdo\Mysql::class, 'ATTR_USE_BUFFERED_QUERY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_LOCAL_INFILE', \Pdo\Mysql::class, 'ATTR_LOCAL_INFILE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_LOCAL_INFILE_DIRECTORY', \Pdo\Mysql::class, 'ATTR_LOCAL_INFILE_DIRECTORY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_INIT_COMMAND', \Pdo\Mysql::class, 'ATTR_INIT_COMMAND'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_MAX_BUFFER_SIZE', \Pdo\Mysql::class, 'ATTR_MAX_BUFFER_SIZE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_READ_DEFAULT_FILE', \Pdo\Mysql::class, 'ATTR_READ_DEFAULT_FILE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_READ_DEFAULT_GROUP', \Pdo\Mysql::class, 'ATTR_READ_DEFAULT_GROUP'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_COMPRESS', \Pdo\Mysql::class, 'ATTR_COMPRESS'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_DIRECT_QUERY', \Pdo\Mysql::class, 'ATTR_DIRECT_QUERY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_FOUND_ROWS', \Pdo\Mysql::class, 'ATTR_FOUND_ROWS'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_IGNORE_SPACE', \Pdo\Mysql::class, 'ATTR_IGNORE_SPACE'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_KEY', \Pdo\Mysql::class, 'ATTR_SSL_KEY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CERT', \Pdo\Mysql::class, 'ATTR_SSL_CERT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CA', \Pdo\Mysql::class, 'ATTR_SSL_CA'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CAPATH', \Pdo\Mysql::class, 'ATTR_SSL_CAPATH'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_CIPHER', \Pdo\Mysql::class, 'ATTR_SSL_CIPHER'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', \Pdo\Mysql::class, 'ATTR_SSL_VERIFY_SERVER_CERT'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_SERVER_PUBLIC_KEY', \Pdo\Mysql::class, 'ATTR_SERVER_PUBLIC_KEY'), new RenameClassAndConstFetch('PDO', 'MYSQL_ATTR_MULTI_STATEMENTS', \Pdo\Mysql::class, 'ATTR_MULTI_STATEMENTS'), new RenameClassAndConstFetch('PDO', 'ODBC_ATTR_USE_CURSOR_LIBRARY', \Pdo\Odbc::class, 'ATTR_USE_CURSOR_LIBRARY'), new RenameClassAndConstFetch('PDO', 'ODBC_ATTR_ASSUME_UTF8', \Pdo\Odbc::class, 'ATTR_ASSUME_UTF8'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_IF_NEEDED', \Pdo\Odbc::class, 'SQL_USE_IF_NEEDED'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_DRIVER', \Pdo\Odbc::class, 'SQL_USE_DRIVER'), new RenameClassAndConstFetch('PDO', 'ODBC_SQL_USE_ODBC', \Pdo\Odbc::class, 'SQL_USE_ODBC'), new RenameClassAndConstFetch('PDO', 'PGSQL_ATTR_DISABLE_PREPARES', \Pdo\Pgsql::class, 'ATTR_DISABLE_PREPARES'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_EXTENDED_RESULT_CODES', \Pdo\Sqlite::class, 'ATTR_EXTENDED_RESULT_CODES'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_OPEN_FLAGS', \Pdo\Sqlite::class, 'OPEN_FLAGS'), new RenameClassAndConstFetch('PDO', 'SQLITE_ATTR_READONLY_STATEMENT', \Pdo\Sqlite::class, 'ATTR_READONLY_STATEMENT'), new RenameClassAndConstFetch('PDO', 'SQLITE_DETERMINISTIC', \Pdo\Sqlite::class, 'DETERMINISTIC'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_READONLY', \Pdo\Sqlite::class, 'OPEN_READONLY'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_READWRITE', \Pdo\Sqlite::class, 'OPEN_READWRITE'), new RenameClassAndConstFetch('PDO', 'SQLITE_OPEN_CREATE', \Pdo\Sqlite::class, 'OPEN_CREATE')], PhpVersion::PHP_85);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_non-standard_cast_names
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameCastRector::class, [new RenameCast(Int_::class, Int_::KIND_INTEGER, Int_::KIND_INT), new RenameCast(Bool_::class, Bool_::KIND_BOOLEAN, Bool_::KIND_BOOL), new RenameCast(Double::class, Double::KIND_DOUBLE, Double::KIND_FLOAT), new RenameCast(String_::class, String_::KIND_BINARY, String_::KIND_STRING)], PhpVersion::PHP_85);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_no-op_functions_from_the_resource_to_object_conversion
    // these function have no effect when use
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RemoveFuncCallRector::class, ['curl_close', 'curl_share_close', 'finfo_close', 'imagedestroy', 'xml_parser_free'], PhpVersion::PHP_85);
    // https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_filter_default_constant
    $rectorConfig->ruleWithConfigurationPhpVersionBound(RenameConstantRector::class, ['FILTER_DEFAULT' => 'FILTER_UNSAFE_RAW'], PhpVersion::PHP_85);
};
