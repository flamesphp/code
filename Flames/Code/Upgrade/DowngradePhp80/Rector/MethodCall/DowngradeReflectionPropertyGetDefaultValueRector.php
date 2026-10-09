<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeReflectionPropertyGetDefaultValueRectorTest
 */
final class DowngradeReflectionPropertyGetDefaultValueRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade ReflectionProperty->getDefaultValue()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run(ReflectionProperty $reflectionProperty)
    {
        return $reflectionProperty->getDefaultValue();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run(ReflectionProperty $reflectionProperty)
    {
        return $reflectionProperty->getDeclaringClass()->getDefaultProperties()[$reflectionProperty->getName()] ?? null;
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
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'getDefaultValue')) {
            return null;
        }
        $objectType = $this->nodeTypeResolver->getType($node->var);
        if (!$objectType instanceof ObjectType) {
            return null;
        }
        if ($objectType->getClassName() !== 'ReflectionProperty') {
            return null;
        }
        $getName = new MethodCall($node->var, 'getName');
        $getDeclaringClassMethodCall = new MethodCall($node->var, 'getDeclaringClass');
        $getDefaultPropertiesMethodCall = new MethodCall($getDeclaringClassMethodCall, 'getDefaultProperties');
        $arrayDimFetch = new ArrayDimFetch($getDefaultPropertiesMethodCall, $getName);
        return new Coalesce($arrayDimFetch, $this->nodeFactory->createNull());
    }
}
