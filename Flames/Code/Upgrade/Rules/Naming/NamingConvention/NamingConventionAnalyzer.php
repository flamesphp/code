<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\NamingConvention;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Util\StringUtils;
final readonly class NamingConventionAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * Matches cases:
     *
     * $someNameSuffix = $this->getSomeName();
     * $prefixSomeName = $this->getSomeName();
     * $someName = $this->getSomeName();
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\MethodCall $expr
     */
    public function isCallMatchingVariableName($expr, string $currentName, string $expectedName): bool
    {
        // skip "$call = $method->call();" based conventions
        $callName = $this->nodeNameResolver->getName($expr->name);
        if ($currentName === $callName) {
            return \true;
        }
        // starts with or ends with
        return StringUtils::isMatch($currentName, '#^(' . $expectedName . '|' . $expectedName . '$)#i');
    }
}
