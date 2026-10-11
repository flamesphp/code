<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\PhpDocNode;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyAnnotation;
final class SymfonyRouteTagValueNodeFactory
{
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     */
    public function createFromItems(array $arrayItemNodes): DoctrineAnnotationTagValueNode
    {
        $identifierTypeNode = new IdentifierTypeNode(SymfonyAnnotation::ROUTE);
        return new DoctrineAnnotationTagValueNode($identifierTypeNode, null, $arrayItemNodes, 'path');
    }
}
