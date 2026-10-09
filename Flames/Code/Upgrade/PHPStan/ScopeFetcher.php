<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class ScopeFetcher
{
    public static function fetch(Node $node): Scope
    {
        /** @var MutatingScope|null $currentScope */
        $currentScope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$currentScope instanceof Scope) {
            $errorMessage = sprintf('Scope not available on "%s" node. Fix scope refresh on changed nodes first', $node::class);
            throw new ShouldNotHappenException($errorMessage);
        }
        return $currentScope;
    }
}
