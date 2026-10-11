<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConstFetch;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/dynamic_class_constant_fetch
 *
 * @see \Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConstFetch\DowngradeDynamicClassConstFetchRectorTest
 */
final class DowngradeDynamicClassConstFetchRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change dynamic class const fetch Example::{$constName} to constant(Example::class . \'::\' . $constName)', [new CodeSample(<<<'CODE_SAMPLE'
$value = Example::{$constName};
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$value = constant(Example::class . '::' . $constName);
CODE_SAMPLE
)]);
    }
    /**
     * @param ClassConstFetch $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->name instanceof Identifier) {
            return null;
        }
        return $this->nodeFactory->createFuncCall('constant', [new Concat(new Concat(new ClassConstFetch($node->class, new Identifier('class')), new String_('::')), $node->name)]);
    }
}
