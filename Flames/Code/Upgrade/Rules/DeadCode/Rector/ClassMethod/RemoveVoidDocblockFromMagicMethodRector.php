<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveVoidDocblockFromMagicMethodRectorTest
 */
final class RemoveVoidDocblockFromMagicMethodRector extends AbstractRector
{
    public function __construct(private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove useless @return void docblock from magic methods __construct, __destruct, and __clone', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return void
     */
    public function __construct() {}

    /**
     * @return void
     */
    public function __destruct() {}

    /**
     * @return void
     */
    public function __clone() {}
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct() {}

    public function __destruct() {}

    public function __clone() {}
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->returnType instanceof Node) {
            return null;
        }
        if (!$this->isNames($node, [MethodName::CONSTRUCT, MethodName::DESTRUCT, MethodName::CLONE])) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $returnTagValueNode = $phpDocInfo->getReturnTagValue();
        if (!$returnTagValueNode instanceof ReturnTagValueNode) {
            return null;
        }
        if ($returnTagValueNode->description !== '') {
            return null;
        }
        if (!$returnTagValueNode->type instanceof IdentifierTypeNode || $returnTagValueNode->type->name !== 'void') {
            return null;
        }
        $phpDocInfo->removeByType(ReturnTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
}
