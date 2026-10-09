<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ValueObjectFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Error;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\ParamRename;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ParamRenameFactory
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function createFromResolvedExpectedName(FunctionLike $functionLike, Param $param, string $expectedName): ?ParamRename
    {
        if ($param->var instanceof Error) {
            return null;
        }
        $currentName = $this->nodeNameResolver->getName($param);
        return new ParamRename($currentName, $expectedName, $param->var, $functionLike);
    }
}
