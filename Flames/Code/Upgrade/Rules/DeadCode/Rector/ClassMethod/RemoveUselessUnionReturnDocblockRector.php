<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRectorTest
 */
final class RemoveUselessUnionReturnDocblockRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove @return union docblock that is broader than a more specific, non-array native return type', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @return bool|mixed|string
     */
    public function run(): false|string
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(): false|string
    {
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
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->returnType === null) {
            return null;
        }
        // skip as no comments
        if ($node->getComments() === []) {
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
        // keep description, it carries info the native type cannot
        if ($returnTagValueNode->description !== '') {
            return null;
        }
        // only union docblock types are handled here
        if (!$returnTagValueNode->type instanceof UnionTypeNode) {
            return null;
        }
        $nativeReturnType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node->returnType);
        // keep array-involving native type (array, ?array, union with array), the docblock may carry element types
        if (!$nativeReturnType->isArray()->no()) {
            return null;
        }
        $docblockReturnType = $phpDocInfo->getReturnType();
        // the docblock adds nothing when it is broader than the native type
        if (!$docblockReturnType->isSuperTypeOf($nativeReturnType)->yes()) {
            return null;
        }
        if (!$phpDocInfo->removeByType(ReturnTagValueNode::class)) {
            return null;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::UNION_TYPES;
    }
}
