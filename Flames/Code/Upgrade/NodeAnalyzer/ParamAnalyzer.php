<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Error;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeManipulator\FuncCallManipulator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class ParamAnalyzer
{
    /**
     * @var string[]
     */
    private const array VARIADIC_FUNCTION_NAMES = ['func_get_arg', 'func_get_args', 'func_num_args', 'get_defined_vars'];
    public function __construct(private NodeComparator $nodeComparator, private NodeNameResolver $nodeNameResolver, private FuncCallManipulator $funcCallManipulator, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private BetterNodeFinder $betterNodeFinder)
    {
    }
    public function isParamUsedInClassMethod(ClassMethod $classMethod, Param $param): bool
    {
        if ($param->isPromoted()) {
            return \true;
        }
        $isParamUsed = \false;
        if ($param->var instanceof Error) {
            return \false;
        }
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classMethod->stmts, function (Node $node) use (&$isParamUsed, $param): ?int {
            if ($this->isVariadicFuncCall($node)) {
                $isParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($this->isUsedAsArg($node, $param)) {
                $isParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            // skip nested anonymous class
            if ($node instanceof Class_ || $node instanceof Function_) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($node instanceof Variable && $this->nodeComparator->areNodesEqual($node, $param->var)) {
                $isParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($node instanceof Closure && $this->isVariableInClosureUses($node, $param->var)) {
                $isParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($this->isParamUsed($node, $param)) {
                $isParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $isParamUsed;
    }
    /**
     * @param Param[] $params
     */
    public function hasPropertyPromotion(array $params): bool
    {
        $found = array_any($params, fn($param) => $param->isPromoted());
        return $found;
    }
    public function isNullable(Param $param): bool
    {
        if ($param->variadic) {
            return \false;
        }
        if (!$param->type instanceof Node) {
            return \false;
        }
        return $param->type instanceof NullableType;
    }
    public function isParamReassign(ClassMethod $classMethod, Param $param): bool
    {
        $paramName = $this->nodeNameResolver->getName($param);
        return (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($classMethod, function (Node $node) use ($paramName): bool {
            if (!$node instanceof Assign) {
                return \false;
            }
            if (!$node->var instanceof Variable) {
                return \false;
            }
            return $this->nodeNameResolver->isName($node->var, $paramName);
        });
    }
    private function isVariableInClosureUses(Closure $closure, Variable $variable): bool
    {
        $found = array_any($closure->uses, fn($use) => $this->nodeComparator->areNodesEqual($use->var, $variable));
        return $found;
    }
    private function isUsedAsArg(Node $node, Param $param): bool
    {
        if ($node instanceof New_ || $node instanceof CallLike) {
            if ($node->isFirstClassCallable()) {
                return \false;
            }
            foreach ($node->getArgs() as $arg) {
                if ($this->nodeComparator->areNodesEqual($param->var, $arg->value)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function isParamUsed(Node $node, Param $param): bool
    {
        if (!$node instanceof FuncCall) {
            return \false;
        }
        if (!$this->nodeNameResolver->isName($node, 'compact')) {
            return \false;
        }
        $arguments = $this->funcCallManipulator->extractArgumentsFromCompactFuncCalls([$node]);
        return $this->nodeNameResolver->isNames($param, $arguments);
    }
    private function isVariadicFuncCall(Node $node): bool
    {
        if (!$node instanceof FuncCall) {
            return \false;
        }
        return $this->nodeNameResolver->isNames($node, self::VARIADIC_FUNCTION_NAMES);
    }
}
