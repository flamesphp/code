<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover\VarTagRemover;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPUnit\Enum\BehatClassName;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedStaticPropertyInBehatContextRectorTest\TypedStaticPropertyInBehatContextRectorTest
 */
final class TypedStaticPropertyInBehatContextRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly VarTagRemover $varTagRemover, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add known property types to Behat context static properties', [new CodeSample(<<<'CODE_SAMPLE'
use Behat\Behat\Context\Context;

final class FeatureContext implements Context
{
    /**
     * @var SomeObject
     */
    public static $someStaticProperty;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Behat\Behat\Context\Context;

final class FeatureContext implements Context
{
    public static ?SomeObject $someStaticProperty = null;
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // no parents
        if (!$node->extends instanceof Name && $node->implements === []) {
            return null;
        }
        if (!$this->isObjectType($node, new ObjectType(BehatClassName::CONTEXT))) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if ($property->type instanceof Node) {
                continue;
            }
            if (!$property->isStatic()) {
                continue;
            }
            if ($this->hasNonNullDefault($property)) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            $varType = $propertyPhpDocInfo->getVarType();
            if (!$varType instanceof ObjectType) {
                continue;
            }
            if ($varType instanceof ShortenedObjectType) {
                $className = $varType->getFullyQualifiedName();
            } else {
                $className = $varType->getClassName();
            }
            $property->type = new NullableType(new FullyQualified($className));
            if (!$property->props[0]->default instanceof Node) {
                $property->props[0]->default = $this->nodeFactory->createNull();
            }
            $this->varTagRemover->removeVarTagIfUseless($propertyPhpDocInfo, $property);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function hasNonNullDefault(Property $property): bool
    {
        $soleProperty = $property->props[0];
        if (!$soleProperty->default instanceof Expr) {
            return \false;
        }
        return !$this->valueResolver->isNull($soleProperty->default);
    }
}
