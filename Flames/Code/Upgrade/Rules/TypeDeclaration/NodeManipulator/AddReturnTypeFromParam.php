<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignRef;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Analyser\Scope;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
final readonly class AddReturnTypeFromParam
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private ReturnTypeInferer $returnTypeInferer)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|null
     */
    public function add($functionLike, Scope $scope)
    {
        if ($functionLike->stmts === null) {
            return null;
        }
        if ($this->shouldSkipNode($functionLike, $scope)) {
            return null;
        }
        $return = $this->findCurrentScopeReturn($functionLike->stmts);
        if (!$return instanceof Return_ || !$return->expr instanceof Expr) {
            return null;
        }
        $returnName = $this->nodeNameResolver->getName($return->expr);
        $stmts = $functionLike->stmts;
        foreach ($functionLike->getParams() as $param) {
            if (!$param->type instanceof Node) {
                continue;
            }
            if ($this->shouldSkipParam($param, $stmts)) {
                continue;
            }
            $paramName = $this->nodeNameResolver->getName($param);
            if ($returnName !== $paramName) {
                continue;
            }
            $functionLike->returnType = $param->type;
            return $functionLike;
        }
        return null;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function findCurrentScopeReturn(array $stmts): ?Return_
    {
        $return = null;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($stmts, static function (Node $node) use (&$return): ?int {
            // skip scope nesting
            if ($node instanceof Class_ || $node instanceof FunctionLike) {
                $return = null;
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Return_) {
                return null;
            }
            if (!$node->expr instanceof Variable) {
                $return = null;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            $return = $node;
            return null;
        });
        return $return;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function shouldSkipParam(Param $param, array $stmts): bool
    {
        $paramName = $this->nodeNameResolver->getName($param);
        $isParamModified = \false;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($stmts, function (Node $node) use ($paramName, &$isParamModified): ?int {
            // skip scope nesting
            if ($node instanceof Class_ || $node instanceof FunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($node instanceof AssignRef && $this->nodeNameResolver->isName($node->expr, $paramName)) {
                $isParamModified = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if (!$node instanceof Assign) {
                return null;
            }
            if (!$node->var instanceof Variable) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->var, $paramName)) {
                return null;
            }
            $isParamModified = \true;
            return NodeVisitor::STOP_TRAVERSAL;
        });
        return $isParamModified;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    private function shouldSkipNode($functionLike, Scope $scope): bool
    {
        // type is already known, skip
        if ($functionLike->returnType instanceof Node) {
            return \true;
        }
        if ($functionLike instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($functionLike, $scope)) {
            return \true;
        }
        $returnType = $this->returnTypeInferer->inferFunctionLike($functionLike);
        if ($returnType instanceof MixedType) {
            return \true;
        }
        $returnType = TypeCombinator::removeNull($returnType);
        return $returnType instanceof UnionType;
    }
}
