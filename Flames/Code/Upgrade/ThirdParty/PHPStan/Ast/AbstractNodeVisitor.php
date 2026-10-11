<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast;

/**
 * Inspired by https://github.com/nikic/PHP-Parser/tree/36a6dcd04e7b0285e8f0868f44bd4927802f7df1
 *
 * Copyright (c) 2011, Nikita Popov
 * All rights reserved.
 */
abstract class AbstractNodeVisitor implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeVisitor
{
    public function beforeTraverse(array $nodes): ?array
    {
        return null;
    }
    public function enterNode(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node $node)
    {
        return null;
    }
    public function leaveNode(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node $node)
    {
        return null;
    }
    public function afterTraverse(array $nodes): ?array
    {
        return null;
    }
}
