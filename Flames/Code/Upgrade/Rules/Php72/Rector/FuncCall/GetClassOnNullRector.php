<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\GetClassOnNullRectorTest
 */
final class GetClassOnNullRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_NULL_ON_GET_CLASS;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Null is no more allowed in `get_class()`', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function getItem()
    {
        $value = null;
        return get_class($value);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function getItem()
    {
        $value = null;
        return $value !== null ? get_class($value) : self::class;
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        $this->traverseNodesWithCallable($node, function (Node $node) use (&$hasChanged) {
            if ($node instanceof Ternary) {
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if (!$node instanceof FuncCall) {
                return null;
            }
            // just created func call
            if ($node->getAttribute(AttributeKey::ORIGINAL_NODE) === null) {
                return null;
            }
            if (!$this->isName($node, 'get_class')) {
                return null;
            }
            if ($node->isFirstClassCallable()) {
                return null;
            }
            $firstArg = $node->getArgs()[0] ?? null;
            if (!$firstArg instanceof Arg) {
                return null;
            }
            $firstArgValue = $firstArg->value;
            $firstArgType = $this->getType($firstArgValue);
            if (!$this->nodeTypeResolver->isNullableType($firstArgValue) && !$firstArgType->isNull()->yes()) {
                return null;
            }
            $notIdentical = new NotIdentical($firstArgValue, $this->nodeFactory->createNull());
            $funcCall = $this->createGetClassFuncCall($node);
            $selfClassConstFetch = $this->nodeFactory->createClassConstReference('self');
            $hasChanged = \true;
            return new Ternary($notIdentical, $funcCall, $selfClassConstFetch);
        });
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function createGetClassFuncCall(FuncCall $oldFuncCall): FuncCall
    {
        return new FuncCall($oldFuncCall->name, $oldFuncCall->args);
    }
}
