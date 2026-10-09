<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Rules\Php74\Guard\MakePropertyTypedGuard;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\JMSTypeAnalyzer;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeFactory\JMSTypePropertyTypeFactory;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\ScalarTypedPropertyFromJMSSerializerAttributeTypeRectorTest
 */
final class ScalarTypedPropertyFromJMSSerializerAttributeTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly MakePropertyTypedGuard $makePropertyTypedGuard, private readonly ReflectionResolver $reflectionResolver, private readonly JMSTypeAnalyzer $jmsTypeAnalyzer, private readonly JMSTypePropertyTypeFactory $jmsTypePropertyTypeFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add scalar typed property from JMS Serializer Type attribute', [new CodeSample(<<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\Type;

final class SomeClass
{
    #[Type('string')]
    private $name;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\Type;

final class SomeClass
{
    #[Type('string')]
    private ?string $name = null;
}
CODE_SAMPLE
)]);
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
        return PhpVersionFeature::ATTRIBUTES;
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->jmsTypeAnalyzer->hasAtLeastOneUntypedPropertyUsingJmsAttribute($node)) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if ($this->shouldSkipProperty($property, $classReflection)) {
                continue;
            }
            $typeValue = $this->jmsTypeAnalyzer->resolveTypeAttributeValue($property);
            if (!is_string($typeValue)) {
                continue;
            }
            $propertyTypeNode = $this->jmsTypePropertyTypeFactory->createScalarTypeNode($typeValue, $property);
            if (!$propertyTypeNode instanceof Identifier) {
                continue;
            }
            $property->type = new NullableType($propertyTypeNode);
            $property->props[0]->default = $this->nodeFactory->createNull();
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function shouldSkipProperty(Property $property, ClassReflection $classReflection): bool
    {
        if ($property->type instanceof Node || $property->props[0]->default instanceof Expr) {
            return \true;
        }
        if (!$this->jmsTypeAnalyzer->hasPropertyJMSTypeAttribute($property)) {
            return \true;
        }
        return !$this->makePropertyTypedGuard->isLegal($property, $classReflection);
    }
}
