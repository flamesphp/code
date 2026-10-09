<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://3v4l.org/Kcs98
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\FuncCall\RestoreIncludePathToIniRestoreRectorTest
 */
final class RestoreIncludePathToIniRestoreRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_RESTORE_INCLUDE_PATH;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change restore_include_path() to ini_restore("include_path")', [new CodeSample(<<<'CODE_SAMPLE'
restore_include_path();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
ini_restore('include_path');
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
        if (!$this->isName($node, 'restore_include_path')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $node->name = new Name('ini_restore');
        $node->args[0] = new Arg(new String_('include_path'));
        return $node;
    }
}
