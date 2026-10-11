<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeCollector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeAnalyzer\ParamAnalyzer;
final readonly class UnusedParameterResolver
{
    public function __construct(private ParamAnalyzer $paramAnalyzer)
    {
    }
    /**
     * @return array<int, Param>
     */
    public function resolve(ClassMethod $classMethod): array
    {
        /** @var array<int, Param> $unusedParameters */
        $unusedParameters = [];
        foreach ($classMethod->params as $i => $param) {
            if ($this->paramAnalyzer->isParamUsedInClassMethod($classMethod, $param)) {
                continue;
            }
            $unusedParameters[$i] = $param;
        }
        return $unusedParameters;
    }
}
