<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\DoctrineTypeAnalyzer;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Matcher\PropertyAssignMatcher;
/**
 * Should add extra null type
 */
final readonly class NullTypeAssignDetector
{
    public function __construct(private DoctrineTypeAnalyzer $doctrineTypeAnalyzer, private NodeTypeResolver $nodeTypeResolver, private PropertyAssignMatcher $propertyAssignMatcher, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser)
    {
    }
    public function detect(ClassLike $classLike, string $propertyName): bool
    {
        $needsNullType = \false;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classLike->stmts, function (Node $node) use ($propertyName, &$needsNullType): ?int {
            $expr = $this->matchAssignExprToPropertyName($node, $propertyName);
            if (!$expr instanceof Expr) {
                return null;
            }
            // not in doctrine property
            $staticType = $this->nodeTypeResolver->getType($expr);
            if ($this->doctrineTypeAnalyzer->isDoctrineCollectionWithIterableUnionType($staticType)) {
                $needsNullType = \false;
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            return null;
        });
        return $needsNullType;
    }
    private function matchAssignExprToPropertyName(Node $node, string $propertyName): ?Expr
    {
        if (!$node instanceof Assign) {
            return null;
        }
        return $this->propertyAssignMatcher->matchPropertyAssignExpr($node, $propertyName);
    }
}
