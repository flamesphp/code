<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Guard\ParamTypeAddGuard;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\VariableInSprintfMaskMatcher;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamStringTypeFromSprintfUseRectorTest
 */
final class AddParamStringTypeFromSprintfUseRector extends AbstractRector
{
    public function __construct(private readonly VariableInSprintfMaskMatcher $variableInSprintfMaskMatcher, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly ParamTypeAddGuard $paramTypeAddGuard)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add string type to parameters used in sprintf calls', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function formatMessage($name)
    {
        return sprintf('My name is %s', $name);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function formatMessage(string $name)
    {
        return sprintf('My name is %s', $name);
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
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }
    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction|null
     */
    public function refactor(Node $node)
    {
        if ($node instanceof ClassMethod && $node->stmts === null) {
            return null;
        }
        if ($node->getParams() === []) {
            return null;
        }
        if ($node instanceof ClassMethod && $this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getParams() as $param) {
            if ($param->type instanceof Node) {
                continue;
            }
            // skip non string default value
            if ($param->default instanceof Expr && !$param->default instanceof String_) {
                continue;
            }
            if (!$this->paramTypeAddGuard->isLegal($param, $node)) {
                continue;
            }
            $variableName = $this->getName($param);
            if (!$this->variableInSprintfMaskMatcher->matchMask($node, $variableName, '%s')) {
                continue;
            }
            $param->type = new Identifier('string');
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
