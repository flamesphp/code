<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Yield_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeNestingScope\ValueObject\ControlStructure;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Reflection\ClassModifierChecker;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\NeverFuncCallAnalyzer;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
final readonly class AddNeverReturnType
{
    public function __construct(private ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private ClassModifierChecker $classModifierChecker, private BetterNodeFinder $betterNodeFinder, private NeverFuncCallAnalyzer $neverFuncCallAnalyzer, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|null
     */
    public function add($node, Scope $scope)
    {
        if ($this->shouldSkip($node, $scope)) {
            return null;
        }
        $node->returnType = new Identifier('never');
        return $node;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $node
     */
    private function shouldSkip($node, Scope $scope): bool
    {
        // already has return type, and non-void
        // it can be "never" return itself, or other return type
        if ($node->returnType instanceof Node && !$this->nodeNameResolver->isName($node->returnType, 'void')) {
            return \true;
        }
        if ($this->hasReturnOrYields($node)) {
            return \true;
        }
        if (!$this->hasNeverNodesOrNeverFuncCalls($node)) {
            return \true;
        }
        if ($node instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return \true;
        }
        if (!$node->returnType instanceof Node) {
            return \false;
        }
        // skip as most likely intentional
        return !$this->classModifierChecker->isInsideFinalClass($node) && $this->nodeNameResolver->isName($node->returnType, 'void');
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $node
     */
    private function hasReturnOrYields($node): bool
    {
        return $this->betterNodeFinder->hasInstancesOfInFunctionLikeScoped($node, array_merge([Return_::class, Yield_::class, YieldFrom::class], ControlStructure::CONDITIONAL_NODE_SCOPE_TYPES));
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $node
     */
    private function hasNeverNodesOrNeverFuncCalls($node): bool
    {
        $hasNeverNodes = (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($node, fn(Node $subNode): bool => $subNode instanceof Expression && $subNode->expr instanceof Throw_);
        if ($hasNeverNodes) {
            return \true;
        }
        return $this->neverFuncCallAnalyzer->hasNeverFuncCall($node);
    }
}
