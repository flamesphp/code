<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator;

use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeAnalyzer\ParamAnalyzer;
use Flames\Code\Upgrade\Rules\Removing\NodeManipulator\ComplexNodeRemover;
final readonly class ClassMethodParamRemover
{
    public function __construct(private ParamAnalyzer $paramAnalyzer, private ComplexNodeRemover $complexNodeRemover)
    {
    }
    public function processRemoveParams(ClassMethod $classMethod): ?ClassMethod
    {
        $paramKeysToBeRemoved = [];
        foreach ($classMethod->params as $key => $param) {
            if ($this->paramAnalyzer->isParamUsedInClassMethod($classMethod, $param)) {
                continue;
            }
            $paramKeysToBeRemoved[] = $key;
        }
        if ($paramKeysToBeRemoved === []) {
            return null;
        }
        $removedParamKeys = $this->complexNodeRemover->processRemoveParamWithKeys($classMethod, $paramKeysToBeRemoved);
        if ($removedParamKeys !== []) {
            return $classMethod;
        }
        return null;
    }
}
