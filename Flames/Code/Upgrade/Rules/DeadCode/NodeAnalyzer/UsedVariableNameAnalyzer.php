<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class UsedVariableNameAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isVariableNamed(Node $node, Variable $variable): bool
    {
        if (($node instanceof MethodCall || $node instanceof PropertyFetch) && ($node->name instanceof Variable && is_string($node->name->name))) {
            return $this->nodeNameResolver->isName($variable, $node->name->name);
        }
        if (!$node instanceof Variable) {
            return \false;
        }
        return $this->nodeNameResolver->areNamesEqual($variable, $node);
    }
}
