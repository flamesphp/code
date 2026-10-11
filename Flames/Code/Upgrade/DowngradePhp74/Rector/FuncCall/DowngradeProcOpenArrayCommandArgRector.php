<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\FuncCall\DowngradeProcOpenArrayCommandArgRectorTest
 */
final class DowngradeProcOpenArrayCommandArgRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change array command argument on proc_open to implode spaced string', [new CodeSample(<<<'CODE_SAMPLE'
return proc_open($command, $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
return proc_open(is_array($command) ? implode(' ', array_map('escapeshellarg', $command)) : $command, $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
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
        if (!$this->isName($node, 'proc_open')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        $commandType = $this->getType($firstArg->value);
        if ($commandType->isString()->yes()) {
            return null;
        }
        $isArrayFuncCall = $this->nodeFactory->createFuncCall('is_array', [new Arg($firstArg->value)]);
        $value = $this->nodeFactory->createFuncCall('array_map', [new Arg(new String_('escapeshellarg')), new Arg($firstArg->value)]);
        $implodeFuncCall = $this->nodeFactory->createFuncCall('implode', [new String_(' '), $value]);
        $firstArg->value = new Ternary($isArrayFuncCall, $implodeFuncCall, $firstArg->value);
        return $node;
    }
}
