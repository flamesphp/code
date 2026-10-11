<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class FunctionLikeManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return string[]
     */
    public function resolveParamNames(FunctionLike $functionLike): array
    {
        $paramNames = [];
        foreach ($functionLike->getParams() as $param) {
            $paramNames[] = $this->nodeNameResolver->getName($param);
        }
        return $paramNames;
    }
}
