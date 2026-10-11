<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
/**
 * This class renames node identifier, e.g. ClassMethod rename:
 *
 * -public function someMethod()
 * +public function newMethod()
 */
final readonly class IdentifierManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param array<string, string> $renameMethodMap
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod $node
     */
    public function renameNodeWithMap($node, array $renameMethodMap): bool
    {
        $oldNodeMethodName = $this->resolveOldMethodName($node);
        if (!is_string($oldNodeMethodName)) {
            return \false;
        }
        $node->name = new Identifier($renameMethodMap[$oldNodeMethodName]);
        return \true;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod $node
     */
    private function resolveOldMethodName($node): ?string
    {
        if ($node instanceof StaticCall || $node instanceof MethodCall) {
            return $this->nodeNameResolver->getName($node->name);
        }
        return $this->nodeNameResolver->getName($node);
    }
}
