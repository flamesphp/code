<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType as NodeUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover\VarTagRemover;
use Flames\Code\Upgrade\ThirdParty\Doctrine\CodeQuality\Enum\CollectionMapping;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Enum\MappingClass;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttrinationFinder;
use Flames\Code\Upgrade\Rules\Php74\Guard\MakePropertyTypedGuard;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeTypeAnalyzer\PropertyTypeDecorator;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\PropertyTypeInferer\AllAssignNodePropertyTypeInferer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRectorTest
 */
final class TypedPropertyFromAssignsRector extends AbstractRector implements MinPhpVersionInterface, ConfigurableRectorInterface
{
    /**
     * @api
     */
    public const string INLINE_PUBLIC = 'inline_public';
    /**
     * Default to false, which only apply changes:
     *
     *  – private modifier property
     *  - protected modifier property on final class without extends or has extends but property and/or its usage only in current class
     *
     * Set to true will allow change other modifiers as well as far as not forbidden, eg: callable type, null type, etc.
     */
    private bool $inlinePublic = \false;
    public function __construct(private readonly AllAssignNodePropertyTypeInferer $allAssignNodePropertyTypeInferer, private readonly PropertyTypeDecorator $propertyTypeDecorator, private readonly VarTagRemover $varTagRemover, private readonly MakePropertyTypedGuard $makePropertyTypedGuard, private readonly ReflectionResolver $reflectionResolver, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ValueResolver $valueResolver, private readonly StaticTypeMapper $staticTypeMapper, private readonly AttrinationFinder $attrinationFinder)
    {
    }
    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->inlinePublic = $configuration[self::INLINE_PUBLIC] ?? (bool) current($configuration);
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add typed property from assigned types', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    private $name;

    public function run()
    {
        $this->name = 'string';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private string|null $name = null;

    public function run()
    {
        $this->name = 'string';
    }
}
CODE_SAMPLE
, [self::INLINE_PUBLIC => \false])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // skip Doctrine static function mapping, properties are mapped in loadMetadata() method
        // @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/reference/php-mapping.html#static-function
        if ($node->getMethod('loadMetadata') instanceof ClassMethod) {
            return null;
        }
        $hasChanged = \false;
        $classReflection = null;
        foreach ($node->getProperties() as $property) {
            // non-private property can be anything with not inline public configured
            if (!$property->isPrivate() && !$this->inlinePublic) {
                continue;
            }
            if ($this->isDoctrineMappedProperty($property)) {
                continue;
            }
            if (!$classReflection instanceof ClassReflection) {
                $classReflection = $this->reflectionResolver->resolveClassReflection($node);
            }
            if (!$classReflection instanceof ClassReflection) {
                return null;
            }
            if (!$this->makePropertyTypedGuard->isLegal($property, $classReflection, $this->inlinePublic)) {
                continue;
            }
            $inferredType = $this->allAssignNodePropertyTypeInferer->inferProperty($property, $classReflection, $this->getFile());
            if (!$inferredType instanceof Type) {
                continue;
            }
            if ($inferredType instanceof MixedType) {
                continue;
            }
            $inferredType = $this->decorateTypeWithNullableIfDefaultPropertyNull($property, $inferredType);
            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($inferredType, TypeKind::PROPERTY);
            if (!$typeNode instanceof Node) {
                continue;
            }
            $hasChanged = \true;
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            if ($inferredType instanceof UnionType && ($typeNode instanceof NodeUnionType || $typeNode instanceof NullableType)) {
                $this->propertyTypeDecorator->decoratePropertyUnionType($inferredType, $typeNode, $property, $phpDocInfo, \false);
            } else {
                $property->type = $typeNode;
            }
            if (!$property->type instanceof Node) {
                continue;
            }
            $this->varTagRemover->removeVarTagIfUseless($phpDocInfo, $property);
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function decorateTypeWithNullableIfDefaultPropertyNull(Property $property, Type $inferredType): Type
    {
        $defaultExpr = $property->props[0]->default;
        if (!$defaultExpr instanceof Expr) {
            return $inferredType;
        }
        if (!$this->valueResolver->isNull($defaultExpr)) {
            return $inferredType;
        }
        if (TypeCombinator::containsNull($inferredType)) {
            return $inferredType;
        }
        return TypeCombinator::addNull($inferredType);
    }
    /**
     * Doctrine properties are handled in doctrine rules
     */
    private function isDoctrineMappedProperty(Property $property): bool
    {
        $mappingClasses = array_merge(CollectionMapping::TO_MANY_CLASSES, CollectionMapping::TO_ONE_CLASSES, [MappingClass::COLUMN]);
        return $this->attrinationFinder->hasByMany($property, $mappingClasses);
    }
}
