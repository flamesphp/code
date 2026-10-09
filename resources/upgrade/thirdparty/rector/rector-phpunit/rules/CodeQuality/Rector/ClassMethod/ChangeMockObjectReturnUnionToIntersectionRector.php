<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareIntersectionTypeNode;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ChangeMockObjectReturnUnionToIntersectionRectorTest
 */
final class ChangeMockObjectReturnUnionToIntersectionRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly PhpDocTypeChanger $phpDocTypeChanger)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $returnTagValueNode = $phpDocInfo->getReturnTagValue();
        if (!$returnTagValueNode instanceof ReturnTagValueNode) {
            return null;
        }
        $returnTypeNode = $returnTagValueNode->type;
        if (!$returnTypeNode instanceof UnionTypeNode) {
            return null;
        }
        // must contain a MockObject or Stub member to be a mock union
        if (!$this->hasMockObjectOrStubType($returnTypeNode)) {
            return null;
        }
        $bracketsAwareIntersectionTypeNode = new BracketsAwareIntersectionTypeNode($returnTypeNode->types);
        $this->phpDocTypeChanger->changeReturnTypeNode($node, $phpDocInfo, $bracketsAwareIntersectionTypeNode);
        return $node;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change a MockObject @return union docblock to an intersection type', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @return Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createEvent(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(Event::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @return Event&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createEvent(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(Event::class);
    }
}
CODE_SAMPLE
)]);
    }
    private function hasMockObjectOrStubType(UnionTypeNode $unionTypeNode): bool
    {
        $found = array_any($unionTypeNode->types, fn($typeNode) => $this->isMockObjectOrStubType($typeNode));
        return $found;
    }
    private function isMockObjectOrStubType(TypeNode $typeNode): bool
    {
        if (!$typeNode instanceof IdentifierTypeNode) {
            return \false;
        }
        $typeName = ltrim($typeNode->name, '\\');
        return in_array($typeName, [PHPUnitClassName::MOCK_OBJECT, 'MockObject', PHPUnitClassName::STUB, 'Stub'], \true);
    }
}
