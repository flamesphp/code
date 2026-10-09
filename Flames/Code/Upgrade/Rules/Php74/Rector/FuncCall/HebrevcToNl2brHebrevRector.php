<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\HebrevcToNl2brHebrevRectorTest
 */
final class HebrevcToNl2brHebrevRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_HEBREVC;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change hebrevc($str) to nl2br(hebrev($str))', [new CodeSample(<<<'CODE_SAMPLE'
hebrevc($str);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
nl2br(hebrev($str));
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
    public function refactor(Node $node): ?FuncCall
    {
        if (!$this->isName($node, 'hebrevc')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $node->name = new Name('hebrev');
        return new FuncCall(new Name('nl2br'), [new Arg($node)]);
    }
}
