<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Rules\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\Class_\RemoveRefactorDuplicatedNodeInstanceCheckRector;
use Flames\Code\Upgrade\Rector\Rector\Class_\AddSeeTestAnnotationRector;
use Flames\Code\Upgrade\Rules\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\AddParamTypeToRefactorMethodRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([DeclareStrictTypesRector::class, PostIncDecToPreIncDecRector::class, FinalizeTestCaseClassRector::class, AddParamTypeToRefactorMethodRector::class, RemoveRefactorDuplicatedNodeInstanceCheckRector::class, AddSeeTestAnnotationRector::class]);
};
