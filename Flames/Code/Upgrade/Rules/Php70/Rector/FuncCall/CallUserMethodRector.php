<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall;

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
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\CallUserMethodRectorTest
 */
final class CallUserMethodRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var array<string, string>
     */
    private const array OLD_TO_NEW_FUNCTIONS = ['call_user_method' => 'call_user_func', 'call_user_method_array' => 'call_user_func_array'];
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_CALL_USER_METHOD;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes call_user_method()/call_user_method_array() to call_user_func()/call_user_func_array()', [new CodeSample('call_user_method($method, $obj, "arg1", "arg2");', 'call_user_func(array(&$obj, "method"), "arg1", "arg2");')]);
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
        $oldFunctionNames = array_keys(self::OLD_TO_NEW_FUNCTIONS);
        if (!$this->isNames($node, $oldFunctionNames)) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!isset(self::OLD_TO_NEW_FUNCTIONS[$this->getName($node)])) {
            return null;
        }
        $newName = self::OLD_TO_NEW_FUNCTIONS[$this->getName($node)];
        $node->name = new Name($newName);
        /** @var Arg[] $oldArgs */
        $oldArgs = $node->args;
        unset($node->args[1]);
        $newArgs = [$this->nodeFactory->createArg([$oldArgs[1]->value, $oldArgs[0]->value])];
        unset($oldArgs[0]);
        unset($oldArgs[1]);
        $node->args = array_merge($newArgs, $oldArgs);
        return $node;
    }
}
