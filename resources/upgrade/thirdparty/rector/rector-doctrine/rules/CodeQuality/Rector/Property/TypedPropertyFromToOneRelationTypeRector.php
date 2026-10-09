<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Doctrine\NodeManipulator\ToOneRelationPropertyTypeResolver;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeTypeAnalyzer\PropertyTypeDecorator;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Property\TypedPropertyFromToOneRelationTypeRectorTest
 */
final class TypedPropertyFromToOneRelationTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly PropertyTypeDecorator $propertyTypeDecorator, private readonly PhpDocTypeChanger $phpDocTypeChanger, private readonly ToOneRelationPropertyTypeResolver $toOneRelationPropertyTypeResolver, private readonly PhpVersionProvider $phpVersionProvider, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly StaticTypeMapper $staticTypeMapper, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Complete @var annotations or types based on @ORM\*toOne annotations or attributes', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

class SimpleColumn
{
    /**
     * @ORM\OneToOne(targetEntity="App\Company\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

class SimpleColumn
{
    /**
     * @ORM\OneToOne(targetEntity="App\Company\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?\App\Company\Entity\Company $company = null;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?\PhpParser\Node\Stmt\Property
    {
        if ($node->type !== null) {
            return null;
        }
        // avoid untyped parent property override, e.g. from a trait used in a parent class
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if ($classReflection instanceof ClassReflection && $this->hasUntypedParentProperty($classReflection, $node)) {
            return null;
        }
        $propertyType = $this->toOneRelationPropertyTypeResolver->resolve($node);
        if (!$propertyType instanceof Type) {
            return null;
        }
        if ($propertyType instanceof MixedType) {
            return null;
        }
        $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::PROPERTY);
        if (!$typeNode instanceof Node) {
            return null;
        }
        $this->completePropertyTypeOrVarDoc($propertyType, $typeNode, $node);
        return $node;
    }
    private function hasUntypedParentProperty(ClassReflection $classReflection, Property $property): bool
    {
        $propertyName = $this->getName($property);
        foreach ($classReflection->getParents() as $parentClassReflection) {
            $nativeReflectionClass = $parentClassReflection->getNativeReflection();
            if (!$nativeReflectionClass->hasProperty($propertyName)) {
                continue;
            }
            // typing the child while the parent property stays untyped is a fatal error
            return $nativeReflectionClass->getProperty($propertyName)->getType() === null;
        }
        return \false;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\ComplexType|\PhpParser\Node\Identifier $typeNode
     */
    private function completePropertyTypeOrVarDoc(Type $propertyType, $typeNode, Property $property): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersion::PHP_74)) {
            if ($propertyType instanceof UnionType) {
                $this->propertyTypeDecorator->decoratePropertyUnionType($propertyType, $typeNode, $property, $phpDocInfo);
                return;
            }
            $property->type = $typeNode;
            return;
        }
        $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $propertyType);
    }
}
