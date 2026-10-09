<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\TypeWithClassName;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\MergeDateTimePropertyTypeDeclarationRectorTest
 */
final class MergeDateTimePropertyTypeDeclarationRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Set DateTime to DateTimeInterface for DateTime property with DateTimeInterface docblock', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var DateTimeInterface
     */
    private DateTime $dateTime;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private DateTimeInterface $dateTime;
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
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if (!$property->type instanceof Node) {
                continue;
            }
            if (!$property->type instanceof FullyQualified) {
                continue;
            }
            if ($property->type->toString() !== 'DateTime') {
                continue;
            }
            if (!$property->isPrivate()) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
            if (!$phpDocInfo instanceof PhpDocInfo) {
                continue;
            }
            $varType = $phpDocInfo->getVarType();
            $className = $varType instanceof TypeWithClassName ? $this->nodeTypeResolver->getFullyQualifiedClassName($varType) : null;
            if ($className === 'DateTimeInterface') {
                $varTagvalueNode = $phpDocInfo->getVarTagValueNode();
                if ($varTagvalueNode instanceof VarTagValueNode && $varTagvalueNode->description === '') {
                    $phpDocInfo->removeByType(VarTagValueNode::class);
                    $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($property);
                }
                $property->type = new FullyQualified('DateTimeInterface');
                $hasChanged = \true;
            }
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
}
