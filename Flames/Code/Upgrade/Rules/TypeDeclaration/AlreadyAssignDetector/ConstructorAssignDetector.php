<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeFinder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeDecorator\StatementDepthAttributeDecorator;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Matcher\PropertyAssignMatcher;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\AutowiredClassMethodOrPropertyAnalyzer;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class ConstructorAssignDetector
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private PropertyAssignMatcher $propertyAssignMatcher, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private AutowiredClassMethodOrPropertyAnalyzer $autowiredClassMethodOrPropertyAnalyzer, private PropertyFetchAnalyzer $propertyFetchAnalyzer, private NodeComparator $nodeComparator)
    {
    }
    public function isPropertyAssignedConditionally(Class_ $class, string $propertyName): bool
    {
        return $this->isPropertyAssigned($class, $propertyName, \true);
    }
    public function isPropertyAssigned(ClassLike $classLike, string $propertyName, bool $allowConditional = \false): bool
    {
        $initializeClassMethods = $this->matchInitializeClassMethod($classLike);
        if ($initializeClassMethods === []) {
            return \false;
        }
        $isAssignedInConstructor = \false;
        StatementDepthAttributeDecorator::decorateClassMethods($initializeClassMethods);
        foreach ($initializeClassMethods as $initializeClassMethod) {
            $this->simpleCallableNodeTraverser->traverseNodesWithCallable((array) $initializeClassMethod->stmts, function (Node $node) use ($propertyName, &$isAssignedInConstructor, $allowConditional): ?int {
                if ($this->isIfElseAssign($node, $propertyName)) {
                    $isAssignedInConstructor = !(bool) $node->getAttribute(AttributeKey::IS_IN_LOOP_OR_SWITCH);
                    return NodeVisitor::STOP_TRAVERSAL;
                }
                $expr = $this->matchAssignExprToPropertyName($node, $propertyName);
                if (!$expr instanceof Expr) {
                    return null;
                }
                /** @var Assign $assign */
                $assign = $node;
                // is merged in assign?
                if ($this->isPropertyUsedInAssign($assign, $propertyName)) {
                    $isAssignedInConstructor = \false;
                    return NodeVisitor::STOP_TRAVERSAL;
                }
                $isFirstLevelStatement = $assign->getAttribute(AttributeKey::IS_FIRST_LEVEL_STATEMENT);
                // cannot be nested
                if ($isFirstLevelStatement !== \true) {
                    if ($allowConditional) {
                        $isAssignedInConstructor = \true;
                        return NodeVisitor::STOP_TRAVERSAL;
                    }
                    return null;
                }
                $isAssignedInConstructor = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            });
        }
        if (!$isAssignedInConstructor) {
            return $this->propertyFetchAnalyzer->isFilledViaMethodCallInConstructStmts($classLike, $propertyName);
        }
        return $isAssignedInConstructor;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function isAssignedInStmts(array $stmts, string $propertyName): bool
    {
        $isAssigned = \false;
        foreach ($stmts as $stmt) {
            // non Expression can be on next stmt
            if (!$stmt instanceof Expression) {
                $isAssigned = \false;
                break;
            }
            if ($this->matchAssignExprToPropertyName($stmt->expr, $propertyName) instanceof Expr) {
                $isAssigned = \true;
            }
        }
        return $isAssigned;
    }
    private function isIfElseAssign(Node $node, string $propertyName): bool
    {
        if (!$node instanceof If_ || $node->elseifs !== [] || !$node->else instanceof Else_) {
            return \false;
        }
        return $this->isAssignedInStmts($node->stmts, $propertyName) && $this->isAssignedInStmts($node->else->stmts, $propertyName);
    }
    private function matchAssignExprToPropertyName(Node $node, string $propertyName): ?Expr
    {
        if (!$node instanceof Assign) {
            return null;
        }
        return $this->propertyAssignMatcher->matchPropertyAssignExpr($node, $propertyName);
    }
    /**
     * @return ClassMethod[]
     */
    private function matchInitializeClassMethod(ClassLike $classLike): array
    {
        $initializingClassMethods = [];
        $constructClassMethod = $classLike->getMethod(MethodName::CONSTRUCT);
        if ($constructClassMethod instanceof ClassMethod) {
            $initializingClassMethods[] = $constructClassMethod;
        }
        $testCaseObjectType = new ObjectType(\PHPUnit\Framework\TestCase::class);
        if ($this->nodeTypeResolver->isObjectType($classLike, $testCaseObjectType)) {
            $setUpClassMethod = $classLike->getMethod(MethodName::SET_UP);
            if ($setUpClassMethod instanceof ClassMethod) {
                $initializingClassMethods[] = $setUpClassMethod;
            }
            $setUpBeforeClassMethod = $classLike->getMethod(MethodName::SET_UP_BEFORE_CLASS);
            if ($setUpBeforeClassMethod instanceof ClassMethod) {
                $initializingClassMethods[] = $setUpBeforeClassMethod;
            }
        }
        foreach ($classLike->getMethods() as $classMethod) {
            if (!$this->autowiredClassMethodOrPropertyAnalyzer->detect($classMethod)) {
                continue;
            }
            $initializingClassMethods[] = $classMethod;
        }
        return $initializingClassMethods;
    }
    private function isPropertyUsedInAssign(Assign $assign, string $propertyName): bool
    {
        $nodeFinder = new NodeFinder();
        $var = $assign->var;
        return (bool) $nodeFinder->findFirst($assign->expr, function (Node $node) use ($propertyName, $var): ?bool {
            if (!$node instanceof PropertyFetch) {
                return null;
            }
            if (!$node->name instanceof Identifier) {
                return null;
            }
            if ($node->name->toString() !== $propertyName) {
                return null;
            }
            return $this->nodeComparator->areNodesEqual($node, $var);
        });
    }
}
