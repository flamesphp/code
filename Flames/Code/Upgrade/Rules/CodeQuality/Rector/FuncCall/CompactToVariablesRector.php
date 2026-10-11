<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\Rules\CodeQuality\CompactConverter;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\CompactToVariablesRectorTest
 */
final class CompactToVariablesRector extends AbstractRector
{
    public function __construct(private readonly CompactConverter $compactConverter)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `compact()` call to own array', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $checkout = 'one';
        $form = 'two';

        return compact('checkout', 'form');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $checkout = 'one';
        $form = 'two';

        return ['checkout' => $checkout, 'form' => $form];
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
        if (!$this->isName($node, 'compact')) {
            return null;
        }
        if ($this->compactConverter->hasAllArgumentsNamed($node)) {
            return $this->compactConverter->convertToArray($node);
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        $firstValue = $firstArg->value;
        $firstValueStaticType = $this->getType($firstValue);
        if (!$firstValueStaticType instanceof ConstantArrayType) {
            return null;
        }
        if ($firstValueStaticType->getIterableValueType() instanceof MixedType) {
            return null;
        }
        return $this->refactorAssignArray($firstValueStaticType);
    }
    private function refactorAssignArray(ConstantArrayType $constantArrayType): ?Array_
    {
        $arrayItems = [];
        foreach ($constantArrayType->getValueTypes() as $valueType) {
            if (!$valueType instanceof ConstantStringType) {
                return null;
            }
            $variableName = $valueType->getValue();
            $variable = new Variable($variableName);
            $arrayItems[] = new ArrayItem($variable, new String_($variableName));
        }
        return new Array_($arrayItems);
    }
}
