<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://3v4l.org/Wgj19
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\RemoveReturnTypeDeclarationFromCloneRectorTest
 */
final class RemoveReturnTypeDeclarationFromCloneRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove return type from __clone() method', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __clone(): void
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function __clone()
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->returnType instanceof Node) {
            return null;
        }
        if (!$this->isName($node, '__clone')) {
            return null;
        }
        $node->returnType = null;
        return $node;
    }
}
