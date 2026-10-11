<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\DynamicClassConstFetchRectorTest
 */
final class DynamicClassConstFetchRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('constant(Example::class . \'::\' . $constName) to dynamic class const fetch Example::{$constName}', [new CodeSample(<<<'CODE_SAMPLE'
constant(Example::class . '::' . $constName);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
Example::{$constName};
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?ClassConstFetch
    {
        if (!$this->isName($node, 'constant')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 1) {
            return null;
        }
        $value = $args[0]->value;
        if (!$value instanceof Concat) {
            return null;
        }
        if (!$value->left instanceof Concat) {
            return null;
        }
        if (!$value->left->left instanceof ClassConstFetch) {
            return null;
        }
        if (!$value->left->left->name instanceof Identifier || $value->left->left->name->toString() !== 'class') {
            return null;
        }
        if (!$value->left->right instanceof String_ || $value->left->right->value !== '::') {
            return null;
        }
        return new ClassConstFetch($value->left->left->class, $value->right);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DYNAMIC_CLASS_CONST_FETCH;
    }
}
