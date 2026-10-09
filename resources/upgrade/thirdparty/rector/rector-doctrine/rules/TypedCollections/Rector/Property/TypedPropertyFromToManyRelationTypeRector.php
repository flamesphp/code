<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\NodeManipulator\ToManyRelationPropertyTypeResolver;
use Flames\Code\Upgrade\Doctrine\TypedCollections\NodeModifier\PropertyDefaultNullRemover;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\Property\TypedPropertyFromToManyRelationTypeRectorTest
 */
final class TypedPropertyFromToManyRelationTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ToManyRelationPropertyTypeResolver $toManyRelationPropertyTypeResolver, private readonly StaticTypeMapper $staticTypeMapper, private readonly PropertyDefaultNullRemover $propertyDefaultNullRemover)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add "Doctrine\Common\Collections\Collection" type declaration, based on @ORM\*toMany and @ODM\*toMany annotations/attributes', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;

class SimpleColumn
{
    /**
     * @ORM\OneToMany(targetEntity="App\Product")
     */
    private $products;

    #[ManyToMany(targetEntity: 'App\Car')]
    private $cars;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\Common\Collections\Collection;

class SimpleColumn
{
    /**
     * @ORM\OneToMany(targetEntity="App\Product")
     */
    private Collection $products;

    #[ManyToMany(targetEntity: 'App\Car')]
    private Collection $cars;
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
    public function refactor(Node $node): ?Property
    {
        if ($node->type !== null && $this->isName($node->type, DoctrineClass::COLLECTION)) {
            return null;
        }
        $propertyType = $this->toManyRelationPropertyTypeResolver->resolve($node);
        if (!$propertyType instanceof Type || $propertyType instanceof MixedType) {
            return null;
        }
        $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::PROPERTY);
        if (!$typeNode instanceof Node) {
            return null;
        }
        // remove default null value if any
        $this->propertyDefaultNullRemover->remove($node);
        if (!$propertyType instanceof UnionType) {
            $node->type = $typeNode;
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
}
