<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit120\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IntersectionTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\PHPUnit120\Rector\Property\MockObjectVarToStubRectorTest
 */
final class MockObjectVarToStubRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getNodeTypes(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Property
    {
        // only inside PHPUnit TestCase scope
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        // only properties already converted to a Stub native type
        if (!$this->isStubNativeType($node->type)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return null;
        }
        if (!$this->replaceMockObjectWithStub($varTagValueNode)) {
            return null;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Update the @var docblock of a property changed to a Stub native type, from MockObject to Stub', [new CodeSample(<<<'CODE_SAMPLE'
/**
 * @var FieldModel|MockObject
 */
private \PHPUnit\Framework\MockObject\Stub $leadFieldModel;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
/**
 * @var FieldModel|\PHPUnit\Framework\MockObject\Stub
 */
private \PHPUnit\Framework\MockObject\Stub $leadFieldModel;
CODE_SAMPLE
)]);
    }
    private function isStubNativeType(?Node $typeNode): bool
    {
        if ($typeNode instanceof IntersectionType) {
            $found = array_any($typeNode->types, fn($innerType) => $this->isStubName($innerType));
            return $found;
        }
        return $this->isStubName($typeNode);
    }
    private function isStubName(?Node $node): bool
    {
        return $node instanceof Node && $this->getName($node) === PHPUnitClassName::STUB;
    }
    private function replaceMockObjectWithStub(VarTagValueNode $varTagValueNode): bool
    {
        $typeNode = $varTagValueNode->type;
        // fresh nodes (without original token positions) are re-printed, mutating in place is not
        if ($typeNode instanceof UnionTypeNode || $typeNode instanceof IntersectionTypeNode) {
            $hasChanged = \false;
            foreach ($typeNode->types as $key => $innerType) {
                if ($innerType instanceof IdentifierTypeNode && $this->isMockObjectIdentifier($innerType)) {
                    $typeNode->types[$key] = new IdentifierTypeNode('\\' . PHPUnitClassName::STUB);
                    $hasChanged = \true;
                }
            }
            return $hasChanged;
        }
        if ($typeNode instanceof IdentifierTypeNode && $this->isMockObjectIdentifier($typeNode)) {
            $varTagValueNode->type = new IdentifierTypeNode('\\' . PHPUnitClassName::STUB);
            return \true;
        }
        return \false;
    }
    private function isMockObjectIdentifier(IdentifierTypeNode $identifierTypeNode): bool
    {
        $lastBackslashPosition = strrpos($identifierTypeNode->name, '\\');
        $shortName = $lastBackslashPosition === \false ? $identifierTypeNode->name : (string) substr($identifierTypeNode->name, $lastBackslashPosition + 1);
        return $shortName === 'MockObject';
    }
}
