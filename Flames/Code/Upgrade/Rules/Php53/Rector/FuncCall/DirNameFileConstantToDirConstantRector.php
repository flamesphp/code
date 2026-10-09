<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php53\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\MagicConst\Dir;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\MagicConst\File;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRectorTest
 */
final class DirNameFileConstantToDirConstantRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Convert `dirname(__FILE__)` to `__DIR__`', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return dirname(__FILE__);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return __DIR__;
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
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'dirname')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (count($node->args) !== 1) {
            return null;
        }
        if (!isset($node->getArgs()[0])) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        if (!$firstArg->value instanceof File) {
            return null;
        }
        return new Dir();
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DIR_CONSTANT;
    }
}
