<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Naming;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Rules\Naming\PhpArray\ArrayFilter;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final class OverriddenExistingNamesResolver
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $overriddenExistingVariableNamesByClassMethod = [];
    public function __construct(private readonly ArrayFilter $arrayFilter, private readonly BetterNodeFinder $betterNodeFinder, private readonly NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    public function hasNameInClassMethodForNew(string $variableName, $functionLike): bool
    {
        $overriddenVariableNames = $this->resolveOverriddenNamesForNew($functionLike);
        return in_array($variableName, $overriddenVariableNames, \true);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $classMethod
     */
    public function hasNameInFunctionLikeForParam(string $expectedName, $classMethod): bool
    {
        /** @var Assign[] $assigns */
        $assigns = $this->betterNodeFinder->findInstanceOf((array) $classMethod->getStmts(), Assign::class);
        $usedVariableNames = [];
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof Variable) {
                continue;
            }
            $variableName = $this->nodeNameResolver->getName($assign->var);
            if ($variableName === null) {
                continue;
            }
            $usedVariableNames[] = $variableName;
        }
        return in_array($expectedName, $usedVariableNames, \true);
    }
    /**
     * @return string[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    private function resolveOverriddenNamesForNew($functionLike): array
    {
        $classMethodId = spl_object_id($functionLike);
        if (isset($this->overriddenExistingVariableNamesByClassMethod[$classMethodId])) {
            return $this->overriddenExistingVariableNamesByClassMethod[$classMethodId];
        }
        $currentlyUsedNames = [];
        /** @var Assign[] $assigns */
        $assigns = $this->betterNodeFinder->findInstanceOf((array) $functionLike->stmts, Assign::class);
        foreach ($assigns as $assign) {
            /** @var Variable $assignVariable */
            $assignVariable = $assign->var;
            $currentVariableName = $this->nodeNameResolver->getName($assignVariable);
            if ($currentVariableName === null) {
                continue;
            }
            $currentlyUsedNames[] = $currentVariableName;
        }
        $currentlyUsedNames = $this->arrayFilter->filterWithAtLeastTwoOccurrences($currentlyUsedNames);
        $this->overriddenExistingVariableNamesByClassMethod[$classMethodId] = $currentlyUsedNames;
        return $currentlyUsedNames;
    }
}
