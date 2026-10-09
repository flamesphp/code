<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\DeadVarTagValueNodeAnalyzer;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromDocblockSetUpDefinedRectorTest
 */
final class TypedPropertyFromDocblockSetUpDefinedRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ConstructorAssignDetector $constructorAssignDetector, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly StaticTypeMapper $staticTypeMapper, private readonly DocBlockUpdater $docBlockUpdater, private readonly DeadVarTagValueNodeAnalyzer $deadVarTagValueNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add property type in PHPUnit test from docblock, if defined in setUp() method', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->container('doctrine.orm.entity_manager');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeClass extends TestCase
{
    private \Doctrine\ORM\EntityManagerInterface $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->container('doctrine.orm.entity_manager');
    }
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
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        // nothing useful here
        $setUpClassMethod = $node->getMethod(MethodName::SET_UP);
        if (!$setUpClassMethod instanceof ClassMethod) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            // already known type
            if ($property->type instanceof Node) {
                continue;
            }
            // some magic might be going on
            if ($property->isStatic()) {
                continue;
            }
            if (!$property->isPrivate()) {
                continue;
            }
            // exactly one property
            if (count($property->props) !== 1) {
                continue;
            }
            $propertyName = $property->props[0]->name->toString();
            if (!$this->constructorAssignDetector->isPropertyAssigned($node, $propertyName)) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
            if (!$propertyPhpDocInfo instanceof PhpDocInfo) {
                continue;
            }
            $varType = $propertyPhpDocInfo->getVarType();
            if ($varType instanceof MixedType) {
                continue;
            }
            $nativePropertyTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($varType, TypeKind::PROPERTY);
            if (!$nativePropertyTypeNode instanceof Node) {
                continue;
            }
            $property->type = $nativePropertyTypeNode;
            // remove var tag
            $this->removeVarTag($propertyPhpDocInfo, $property);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    private function removeVarTag(PhpDocInfo $propertyPhpDocInfo, Property $property): void
    {
        $varTagValueNode = $propertyPhpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        if (!$this->deadVarTagValueNodeAnalyzer->isDead($varTagValueNode, $property)) {
            return;
        }
        $propertyPhpDocInfo->removeByType(VarTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($property);
    }
}
