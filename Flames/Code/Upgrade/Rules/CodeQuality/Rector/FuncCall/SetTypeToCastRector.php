<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Bool_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Double;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Object_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SetTypeToCastRectorTest
 */
final class SetTypeToCastRector extends AbstractRector
{
    /**
     * @var array<string, class-string<Cast>>
     */
    private const array TYPE_TO_CAST = ['array' => Array_::class, 'bool' => Bool_::class, 'boolean' => Bool_::class, 'double' => Double::class, 'float' => Double::class, 'int' => Int_::class, 'integer' => Int_::class, 'object' => Object_::class, 'string' => String_::class];
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `settype()` to `(type)` on standalone line. `settype()` returns always success/failure bool value', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($foo)
    {
        settype($foo, 'string');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($foo)
    {
        $foo = (string) $foo;
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
        return [Expression::class];
    }
    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression
    {
        // skip expr that are not standalone line, as settype() returns success bool value
        // and cannot be casted
        if (!$node->expr instanceof FuncCall) {
            return null;
        }
        $assign = $this->refactorFuncCall($node->expr);
        if (!$assign instanceof Assign) {
            return null;
        }
        return new Expression($assign);
    }
    private function refactorFuncCall(FuncCall $funcCall): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign
    {
        if (!$this->isName($funcCall, 'setType')) {
            return null;
        }
        if ($funcCall->isFirstClassCallable()) {
            return null;
        }
        if (count($funcCall->getArgs()) < 2) {
            return null;
        }
        $secondArg = $funcCall->getArgs()[1];
        $typeValue = $this->valueResolver->getValue($secondArg->value);
        if (!is_string($typeValue)) {
            return null;
        }
        $typeValue = strtolower($typeValue);
        $firstArg = $funcCall->getArgs()[0];
        $variable = $firstArg->value;
        if (isset(self::TYPE_TO_CAST[$typeValue])) {
            $castClass = self::TYPE_TO_CAST[$typeValue];
            $castNode = new $castClass($variable);
            return new Assign($variable, $castNode);
        }
        if ($typeValue === 'null') {
            return new Assign($variable, $this->nodeFactory->createNull());
        }
        return null;
    }
}
