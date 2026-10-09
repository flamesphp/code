<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\MethodName;
/**
 * Shared logic for TypedPropertyFromContainerGetSetUpUpgrade and TypedPropertyFromGetRepositorySetUpRector.
 */
final readonly class SetUpAssignedPropertyTyper
{
    public function __construct(private TestsNodeAnalyzer $testsNodeAnalyzer, private PhpDocInfoFactory $phpDocInfoFactory, private StaticTypeMapper $staticTypeMapper, private DocBlockUpdater $docBlockUpdater, private BetterNodeFinder $betterNodeFinder, private ReflectionProvider $reflectionProvider, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param callable(Expr): bool $isTargetAssignExpr
     */
    public function refactorClass(Class_ $class, callable $isTargetAssignExpr): ?Class_
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($class)) {
            return null;
        }
        $setUpClassMethod = $class->getMethod(MethodName::SET_UP);
        if (!$setUpClassMethod instanceof ClassMethod) {
            return null;
        }
        $hasChanged = \false;
        foreach ($class->getProperties() as $property) {
            // type is already set
            if ($property->type instanceof Node) {
                continue;
            }
            if (!$property->isPrivate()) {
                continue;
            }
            if ($property->isStatic()) {
                continue;
            }
            // exactly one property
            if (count($property->props) !== 1) {
                continue;
            }
            $propertyName = $this->nodeNameResolver->getName($property->props[0]);
            if (!$this->isAssignedInSetUp($setUpClassMethod, $propertyName, $isTargetAssignExpr)) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
            if (!$propertyPhpDocInfo instanceof PhpDocInfo) {
                continue;
            }
            $varType = $propertyPhpDocInfo->getVarType();
            if (!$varType instanceof ObjectType) {
                continue;
            }
            $propertyTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($varType, TypeKind::PROPERTY);
            if (!$propertyTypeNode instanceof Name) {
                continue;
            }
            // must be an existing object type
            if (!$this->reflectionProvider->hasClass($propertyTypeNode->toString())) {
                continue;
            }
            $property->type = $propertyTypeNode;
            $this->removeVarTag($propertyPhpDocInfo, $property);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $class;
        }
        return null;
    }
    /**
     * @param callable(Expr): bool $isTargetAssignExpr
     */
    private function isAssignedInSetUp(ClassMethod $setUpClassMethod, ?string $propertyName, callable $isTargetAssignExpr): bool
    {
        /** @var Assign[] $assigns */
        $assigns = $this->betterNodeFinder->findInstanceOf($setUpClassMethod, Assign::class);
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof PropertyFetch) {
                continue;
            }
            $propertyFetch = $assign->var;
            if (!$this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($propertyFetch, (string) $propertyName)) {
                continue;
            }
            if ($isTargetAssignExpr($assign->expr)) {
                return \true;
            }
        }
        return \false;
    }
    private function removeVarTag(PhpDocInfo $propertyPhpDocInfo, Property $property): void
    {
        $varTagValueNode = $propertyPhpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        $propertyPhpDocInfo->removeByType(VarTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($property);
    }
}
