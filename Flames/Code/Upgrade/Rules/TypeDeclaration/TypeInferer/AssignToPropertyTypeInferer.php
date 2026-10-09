<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitor;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\NullTypeAssignDetector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\PropertyDefaultAssignDetector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Matcher\PropertyAssignMatcher;
/**
 * @internal
 */
final readonly class AssignToPropertyTypeInferer
{
    public function __construct(private ConstructorAssignDetector $constructorAssignDetector, private PropertyAssignMatcher $propertyAssignMatcher, private PropertyDefaultAssignDetector $propertyDefaultAssignDetector, private NullTypeAssignDetector $nullTypeAssignDetector, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private TypeFactory $typeFactory, private NodeTypeResolver $nodeTypeResolver, private ExprAnalyzer $exprAnalyzer, private ValueResolver $valueResolver, private PropertyFetchAnalyzer $propertyFetchAnalyzer, private PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    public function inferPropertyInClassLike(Property $property, string $propertyName, ClassLike $classLike): ?Type
    {
        if ($this->hasAssignDynamicPropertyValue($classLike, $propertyName)) {
            return null;
        }
        $assignedExprTypes = $this->getAssignedExprTypes($classLike, $property, $propertyName);
        if ($this->shouldAddNullType($classLike, $propertyName, $assignedExprTypes)) {
            $assignedExprTypes[] = new NullType();
        }
        return $this->resolveTypeWithVerifyDefaultValue($property, $assignedExprTypes);
    }
    /**
     * @param Type[] $assignedExprTypes
     */
    private function resolveTypeWithVerifyDefaultValue(Property $property, array $assignedExprTypes): ?Type
    {
        $defaultPropertyValue = $property->props[0]->default;
        if ($assignedExprTypes === []) {
            // not typed, never assigned, but has default value, then pull type from default value
            if (!$property->type instanceof Node && $defaultPropertyValue instanceof Expr) {
                return $this->nodeTypeResolver->getType($defaultPropertyValue);
            }
            return null;
        }
        $inferredType = $this->typeFactory->createMixedPassedOrUnionType($assignedExprTypes);
        if ($this->shouldSkipWithDifferentDefaultValueType($defaultPropertyValue, $inferredType)) {
            return null;
        }
        return $inferredType;
    }
    private function shouldSkipWithDifferentDefaultValueType(?Expr $expr, Type $inferredType): bool
    {
        if (!$expr instanceof Expr) {
            return \false;
        }
        if ($this->valueResolver->isNull($expr)) {
            return \false;
        }
        $defaultType = $this->nodeTypeResolver->getNativeType($expr);
        return $inferredType->isSuperTypeOf($defaultType)->no();
    }
    private function resolveExprStaticTypeIncludingDimFetch(Assign $assign): Type
    {
        $exprStaticType = $this->nodeTypeResolver->getNativeType($assign->expr);
        if ($assign->var instanceof ArrayDimFetch) {
            return new ArrayType(new MixedType(), $exprStaticType);
        }
        return $exprStaticType;
    }
    /**
     * @param Type[] $assignedExprTypes
     */
    private function shouldAddNullType(ClassLike $classLike, string $propertyName, array $assignedExprTypes): bool
    {
        $hasPropertyDefaultValue = $this->propertyDefaultAssignDetector->detect($classLike, $propertyName);
        $isAssignedInConstructor = $this->constructorAssignDetector->isPropertyAssigned($classLike, $propertyName);
        if ($assignedExprTypes === [] && ($isAssignedInConstructor || $hasPropertyDefaultValue)) {
            return \false;
        }
        $shouldAddNullType = $this->nullTypeAssignDetector->detect($classLike, $propertyName);
        if ($shouldAddNullType) {
            if ($isAssignedInConstructor) {
                return \false;
            }
            return !$hasPropertyDefaultValue;
        }
        if ($assignedExprTypes === []) {
            return \false;
        }
        if ($isAssignedInConstructor) {
            return \false;
        }
        return !$hasPropertyDefaultValue;
    }
    private function hasAssignDynamicPropertyValue(ClassLike $classLike, string $propertyName): bool
    {
        $hasAssignDynamicPropertyValue = \false;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classLike->stmts, function (Node $node) use ($propertyName, &$hasAssignDynamicPropertyValue): ?int {
            if (!$node instanceof Assign) {
                return null;
            }
            $expr = $this->propertyAssignMatcher->matchPropertyAssignExpr($node, $propertyName);
            if (!$expr instanceof Expr) {
                if (!$this->propertyFetchAnalyzer->isLocalPropertyFetch($node->var)) {
                    return null;
                }
                /** @var PropertyFetch|StaticPropertyFetch $assignVar */
                $assignVar = $node->var;
                if (!$assignVar->name instanceof Identifier) {
                    $hasAssignDynamicPropertyValue = \true;
                    return NodeVisitor::STOP_TRAVERSAL;
                }
                return null;
            }
            return null;
        });
        return $hasAssignDynamicPropertyValue;
    }
    /**
     * @return array<Type>
     */
    private function getAssignedExprTypes(ClassLike $classLike, Property $property, string $propertyName): array
    {
        $assignedExprTypes = [];
        $hasJmsType = $this->phpAttributeAnalyzer->hasPhpAttribute($property, ClassName::JMS_TYPE);
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classLike->stmts, function (Node $node) use ($propertyName, &$assignedExprTypes, $hasJmsType): ?int {
            if (!$node instanceof Assign) {
                return null;
            }
            $expr = $this->propertyAssignMatcher->matchPropertyAssignExpr($node, $propertyName);
            if (!$expr instanceof Expr) {
                return null;
            }
            if ($this->exprAnalyzer->isNonTypedFromParam($node->expr)) {
                $assignedExprTypes = $hasJmsType ? [] : [new MixedType()];
                return NodeVisitor::STOP_TRAVERSAL;
            }
            $assignedExprTypes[] = $this->resolveExprStaticTypeIncludingDimFetch($node);
            return null;
        });
        return $assignedExprTypes;
    }
}
