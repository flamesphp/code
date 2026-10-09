<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFinder;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class VariableFinder
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return Variable[]
     */
    public function find(Node $node, string $variableName): array
    {
        $variables = $this->betterNodeFinder->findInstancesOfScoped([$node], Variable::class);
        return array_filter($variables, fn(Variable $variable): bool => $this->nodeNameResolver->isName($variable, $variableName));
    }
}
