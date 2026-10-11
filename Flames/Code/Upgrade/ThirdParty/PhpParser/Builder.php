<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser;

interface Builder
{
    /**
     * Returns the built node.
     *
     * @return Node The built node
     */
    public function getNode(): \Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
}
