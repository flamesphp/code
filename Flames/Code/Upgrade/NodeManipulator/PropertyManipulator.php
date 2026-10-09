<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\Doctrine\ORM\Mapping\Table;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeNestingScope\ContextAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PromotedPropertyResolver;
use Flames\Code\Upgrade\PhpParser\AstResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\NodeFinder\PropertyFetchFinder;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use Flames\Code\Upgrade\ValueObject\MethodName;
/**
 * For inspiration to improve this service,
 * @see examples of variable modifications in https://wiki.php.net/rfc/readonly_properties_v2#proposal
 */
final readonly class PropertyManipulator
{
    /**
     * @var string[]|class-string<Table>[]
     */
    private const array ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS = [
        'ApiPlatform\Core\Annotation\ApiResource',
        'ApiPlatform\Metadata\ApiResource',
        'Doctrine\ORM\Mapping\Entity',
        'Doctrine\ORM\Mapping\Table',
        'Doctrine\ORM\Mapping\MappedSuperclass',
        'Doctrine\ORM\Mapping\Embeddable',
        // Deprecated in ODM 2.16
        'Doctrine\ODM\MongoDB\Mapping\Annotations\Document',
        'Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument',
        // New in ODM 2.16
        'Doctrine\ODM\MongoDB\Mapping\Attribute\Document',
        'Doctrine\ODM\MongoDB\Mapping\Attribute\EmbeddedDocument',
    ];
    public function __construct(private BetterNodeFinder $betterNodeFinder, private PhpDocInfoFactory $phpDocInfoFactory, private PropertyFetchFinder $propertyFetchFinder, private NodeNameResolver $nodeNameResolver, private PhpAttributeAnalyzer $phpAttributeAnalyzer, private NodeTypeResolver $nodeTypeResolver, private PromotedPropertyResolver $promotedPropertyResolver, private ConstructorAssignDetector $constructorAssignDetector, private AstResolver $astResolver, private PropertyFetchAnalyzer $propertyFetchAnalyzer, private ContextAnalyzer $contextAnalyzer)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $propertyOrParam
     */
    public function isPropertyChangeableExceptConstructor(Class_ $class, $propertyOrParam, Scope $scope): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($class);
        if ($this->hasAllowedNotReadonlyAnnotationOrAttribute($phpDocInfo, $class)) {
            return \true;
        }
        if ($this->phpAttributeAnalyzer->hasPhpAttribute($propertyOrParam, ClassName::JMS_TYPE)) {
            return \true;
        }
        $propertyFetches = $this->propertyFetchFinder->findPrivatePropertyFetches($class, $propertyOrParam, $scope);
        $classMethod = $class->getMethod(MethodName::CONSTRUCT);
        foreach ($propertyFetches as $propertyFetch) {
            if ($this->contextAnalyzer->isChangeableContext($propertyFetch)) {
                return \true;
            }
            // skip for constructor? it is allowed to set value in constructor method
            $propertyName = (string) $this->nodeNameResolver->getName($propertyFetch);
            if ($this->isPropertyAssignedOnlyInConstructor($class, $propertyName, $propertyFetch, $classMethod)) {
                continue;
            }
            if ($this->contextAnalyzer->isLeftPartOfAssign($propertyFetch)) {
                return \true;
            }
            if ($propertyFetch->getAttribute(AttributeKey::IS_UNSET_VAR) === \true) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @api Used in rector-symfony
     */
    public function resolveExistingClassPropertyNameByType(Class_ $class, ObjectType $objectType): ?string
    {
        foreach ($class->getProperties() as $property) {
            $propertyType = $this->nodeTypeResolver->getType($property);
            if (!$propertyType->equals($objectType)) {
                continue;
            }
            return $this->nodeNameResolver->getName($property);
        }
        $promotedPropertyParams = $this->promotedPropertyResolver->resolveFromClass($class);
        foreach ($promotedPropertyParams as $promotedPropertyParam) {
            $paramType = $this->nodeTypeResolver->getType($promotedPropertyParam);
            if (!$paramType->equals($objectType)) {
                continue;
            }
            return $this->nodeNameResolver->getName($promotedPropertyParam);
        }
        return null;
    }
    public function isUsedByTrait(ClassReflection $classReflection, string $propertyName): bool
    {
        foreach ($classReflection->getTraits() as $traitUse) {
            $trait = $this->astResolver->resolveClassFromClassReflection($traitUse);
            if (!$trait instanceof Trait_) {
                continue;
            }
            if ($this->propertyFetchAnalyzer->containsLocalPropertyFetchName($trait, $propertyName)) {
                return \true;
            }
        }
        return \false;
    }
    public function hasTraitWithSamePropertyOrWritten(ClassReflection $classReflection, string $propertyName): bool
    {
        foreach ($classReflection->getTraits() as $traitUse) {
            if ($traitUse->hasInstanceProperty($propertyName) || $traitUse->hasStaticProperty($propertyName)) {
                return \true;
            }
            $trait = $this->astResolver->resolveClassFromClassReflection($traitUse);
            if (!$trait instanceof Trait_) {
                continue;
            }
            // is property written to
            if ($this->propertyFetchAnalyzer->containsWrittenPropertyFetchName($trait, $propertyName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch $propertyFetch
     */
    private function isPropertyAssignedOnlyInConstructor(Class_ $class, string $propertyName, $propertyFetch, ?ClassMethod $classMethod): bool
    {
        if (!$classMethod instanceof ClassMethod) {
            return \false;
        }
        $node = $this->betterNodeFinder->findFirst((array) $classMethod->stmts, static fn(Node $subNode): bool => ($subNode instanceof PropertyFetch || $subNode instanceof StaticPropertyFetch) && $subNode === $propertyFetch);
        // there is property unset in Test class, so only check on __construct
        if (!$node instanceof Node) {
            return \false;
        }
        return $this->constructorAssignDetector->isPropertyAssigned($class, $propertyName);
    }
    private function hasAllowedNotReadonlyAnnotationOrAttribute(PhpDocInfo $phpDocInfo, Class_ $class): bool
    {
        if ($phpDocInfo->hasByAnnotationClasses(self::ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS)) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttributes($class, self::ALLOWED_NOT_READONLY_CLASS_ANNOTATIONS);
    }
}
