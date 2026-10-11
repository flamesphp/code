<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class PropertyFetchAssignManipulator
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private NodeNameResolver $nodeNameResolver, private PropertyFetchAnalyzer $propertyFetchAnalyzer)
    {
    }
    public function isAssignedMultipleTimesInConstructor(Class_ $class, Property $property): bool
    {
        $classMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$classMethod instanceof ClassMethod) {
            return \false;
        }
        $count = 0;
        $propertyName = $this->nodeNameResolver->getName($property);
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable((array) $classMethod->getStmts(), function (Node $node) use ($propertyName, &$count): ?int {
            // skip anonymous classes and inner function
            if ($node instanceof Class_ || $node instanceof Function_) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Assign && !$node instanceof AssignOp) {
                return null;
            }
            if (!$this->propertyFetchAnalyzer->isLocalPropertyFetchName($node->var, $propertyName)) {
                return null;
            }
            ++$count;
            if ($count === 2) {
                return NodeVisitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $count === 2;
    }
}
