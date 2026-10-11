<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeReflectionGetAttributesRectorTest
 */
final class DowngradeReflectionGetAttributesRector extends AbstractRector
{
    private const string IS_IF_TERNARY = 'is_if_ternary';
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove reflection getAttributes() class method code', [new CodeSample(<<<'CODE_SAMPLE'
function run(ReflectionClass $reflectionClass)
{
    return $reflectionClass->getAttributes();
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function run(ReflectionClass $reflectionClass)
{
    return method_exists($reflectionClass, 'getAttributes') ? $reflectionClass->getAttributes() ? [];
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Ternary::class, MethodCall::class];
    }
    /**
     * @param Ternary|MethodCall $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary
    {
        if ($node instanceof Ternary) {
            if ($node->if instanceof Expr && $node->cond instanceof FuncCall && $this->isName($node->cond, 'method_exists')) {
                $node->if->setAttribute(self::IS_IF_TERNARY, \true);
            }
            return null;
        }
        if (!$this->isName($node->name, 'getAttributes')) {
            return null;
        }
        if ($node->getAttribute(self::IS_IF_TERNARY) === \true) {
            return null;
        }
        if (!$this->isObjectType($node->var, new ObjectType('Reflector'))) {
            return null;
        }
        $args = [new Arg($node->var), new Arg(new String_('getAttributes'))];
        $methodExistsFuncCall = $this->nodeFactory->createFuncCall('method_exists', $args);
        return new Ternary($methodExistsFuncCall, $node, new Array_([]));
    }
}
