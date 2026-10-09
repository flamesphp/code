<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
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
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([Php4ConstructorRector::class, TernaryToNullCoalescingRector::class, RandomFunctionRector::class, ExceptionHandlerTypehintRector::class, MultiDirnameRector::class, ListSplitStringRector::class, EmptyListRector::class, CallUserMethodRector::class, EregToPregMatchRector::class, ReduceMultipleDefaultSwitchRector::class, TernaryToSpaceshipRector::class, WrapVariableVariableNameInCurlyBracesRector::class, IfToSpaceshipRector::class, ThisCallOnStaticMethodToStaticCallRector::class, BreakNotInLoopOrSwitchToReturnRector::class, RenameMktimeWithoutArgsToTimeRector::class, IfIssetToCoalescingRector::class]);
};
