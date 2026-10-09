<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\AssignToPropertyTypeInferer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRectorTest
 */
final class TypedPropertyFromCreateMockAssignRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly AssignToPropertyTypeInferer $assignToPropertyTypeInferer, private readonly StaticTypeMapper $staticTypeMapper, private readonly ConstructorAssignDetector $constructorAssignDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add "PHPUnit\Framework\MockObject\MockObject" typed property from assigned mock to clearly separate from real objects', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private $someProperty;

    protected function setUp(): void
    {
        $this->someProperty = $this->createMock(SomeMockedClass::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class SomeTest extends TestCase
{
    private MockObject $someProperty;

    protected function setUp(): void
    {
        $this->someProperty = $this->createMock(SomeMockedClass::class);
    }
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
        if (!$this->isObjectType($node, new ObjectType(ClassName::TEST_CASE_CLASS))) {
            return null;
        }
        $hasChanged = \false;
        $mockObjectType = new ObjectType(ClassName::MOCK_OBJECT);
        foreach ($node->getProperties() as $property) {
            if (count($property->props) !== 1) {
                continue;
            }
            // already use PHPUnit\Framework\MockObject\MockObject type
            if ($this->isAlreadyTypedWithMockObject($property, $mockObjectType)) {
                continue;
            }
            $propertyName = (string) $this->getName($property);
            $type = $this->assignToPropertyTypeInferer->inferPropertyInClassLike($property, $propertyName, $node);
            if (!$type instanceof Type) {
                continue;
            }
            $propertyType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PROPERTY);
            if (!$propertyType instanceof Node) {
                continue;
            }
            if (!$this->isObjectType($propertyType, $mockObjectType)) {
                continue;
            }
            if (!$this->constructorAssignDetector->isPropertyAssigned($node, $propertyName)) {
                if (!$propertyType instanceof NullableType) {
                    continue;
                }
                $property->props[0]->default = $this->nodeFactory->createNull();
            }
            $property->type = $propertyType;
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    private function isAlreadyTypedWithMockObject(Property $property, ObjectType $mockObjectType): bool
    {
        if (!$property->type instanceof Node) {
            return \false;
        }
        // complex type, used on purpose
        if ($property->type instanceof IntersectionType) {
            return \true;
        }
        return $this->isObjectType($property->type, $mockObjectType);
    }
}
