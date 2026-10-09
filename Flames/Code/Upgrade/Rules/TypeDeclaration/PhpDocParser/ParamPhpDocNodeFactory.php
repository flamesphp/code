<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\PhpDocParser;

use PhpParser\Node\Param;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ParamPhpDocNodeFactory
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function create(TypeNode $typeNode, Param $param): ParamTagValueNode
    {
        return new ParamTagValueNode($typeNode, $param->variadic, '$' . $this->nodeNameResolver->getName($param), '', \false);
    }
}
