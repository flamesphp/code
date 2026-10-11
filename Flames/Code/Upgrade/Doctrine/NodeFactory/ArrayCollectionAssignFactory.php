<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
final readonly class ArrayCollectionAssignFactory
{
    public function __construct(private NodeFactory $nodeFactory)
    {
    }
    public function createFromPropertyName(string $toManyPropertyName): Expression
    {
        $propertyFetch = $this->nodeFactory->createPropertyFetch('this', $toManyPropertyName);
        $new = new New_(new FullyQualified(DoctrineClass::ARRAY_COLLECTION));
        $assign = new Assign($propertyFetch, $new);
        return new Expression($assign);
    }
}
