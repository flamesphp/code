<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\ArrowFunction;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php72\NodeFactory\AnonymousFunctionFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://www.php.net/manual/en/functions.arrow.php
 *
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRectorTest
 */
final class ArrowFunctionToAnonymousFunctionRector extends AbstractRector
{
    public function __construct(private readonly AnonymousFunctionFactory $anonymousFunctionFactory, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace arrow functions with anonymous functions', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $delimiter = ",";
        $callable = fn($matches) => $delimiter . strtolower($matches[1]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $delimiter = ",";
        $callable = function ($matches) use ($delimiter) {
            return $delimiter . strtolower($matches[1]);
        };
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
        return [ArrowFunction::class];
    }
    /**
     * @param ArrowFunction $node
     */
    public function refactor(Node $node): Closure
    {
        $stmts = [new Return_($node->expr)];
        $anonymousFunctionFactory = $this->anonymousFunctionFactory->create($node->params, $stmts, $node->returnType, $node->static);
        if ($node->expr instanceof Assign && $node->expr->expr instanceof Variable) {
            $isFound = (bool) $this->betterNodeFinder->findFirst($anonymousFunctionFactory->uses, fn(Node $subNode): bool => $subNode instanceof Variable && $this->nodeComparator->areNodesEqual($subNode, $node->expr->expr));
            if (!$isFound) {
                $isAlsoParam = in_array($node->expr->expr->name, array_map(static fn(Param $param) => $param->var instanceof Variable ? $param->var->name : null, $node->params));
                if (!$isAlsoParam) {
                    $anonymousFunctionFactory->uses[] = new ClosureUse($node->expr->expr);
                }
            }
        }
        // downgrade "return throw"
        $this->traverseNodesWithCallable($anonymousFunctionFactory, static function (Node $node): ?Expression {
            if (!$node instanceof Return_) {
                return null;
            }
            if (!$node->expr instanceof Throw_) {
                return null;
            }
            // throw expr to throw stmts
            return new Expression($node->expr);
        });
        $this->appendUsesFromInsertedVariable($node->expr, $anonymousFunctionFactory);
        return $anonymousFunctionFactory;
    }
    private function appendUsesFromInsertedVariable(Expr $expr, Closure $anonymousFunctionFactory): void
    {
        $this->traverseNodesWithCallable($expr, function (Node $subNode) use ($anonymousFunctionFactory) {
            if (!$subNode instanceof Variable) {
                return null;
            }
            $variableName = $this->getName($subNode);
            if ($variableName === null) {
                return null;
            }
            if ($subNode->hasAttribute(AttributeKey::ORIGINAL_NODE)) {
                return null;
            }
            $scope = $subNode->getAttribute(AttributeKey::SCOPE);
            if (!$scope instanceof Scope) {
                return null;
            }
            if (!$scope->hasVariableType($variableName)->yes()) {
                $anonymousFunctionFactory->uses[] = new ClosureUse(new Variable($variableName));
            }
        });
    }
}
