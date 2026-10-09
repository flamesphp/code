<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class FormCollectionAnalyzer
{
    public function __construct(private ValueResolver $valueResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isCollectionType(MethodCall $methodCall): bool
    {
        $typeValue = $methodCall->getArgs()[1]->value;
        if (!$typeValue instanceof ClassConstFetch) {
            return $this->valueResolver->isValue($typeValue, 'collection');
        }
        if (!$this->nodeNameResolver->isName($typeValue->class, 'Symfony\Component\Form\Extension\Core\Type\CollectionType')) {
            return $this->valueResolver->isValue($typeValue, 'collection');
        }
        return \true;
    }
}
