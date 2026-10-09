<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFinder;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class PropertyFetchUsageFinder
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder)
    {
    }
    /**
     * @return PropertyFetch[]
     */
    public function findInCallLikes(Class_ $class, string $propertyName): array
    {
        /** @var CallLike[] $callLikes */
        $callLikes = $this->betterNodeFinder->findInstancesOfScoped($class->getMethods(), CallLike::class);
        $propertyFetchesInNewArgs = [];
        foreach ($callLikes as $callLike) {
            if ($callLike->isFirstClassCallable()) {
                continue;
            }
            foreach ($callLike->getArgs() as $arg) {
                if (!$arg->value instanceof PropertyFetch) {
                    continue;
                }
                if (!$this->nodeNameResolver->isName($arg->value->name, $propertyName)) {
                    continue;
                }
                $propertyFetchesInNewArgs[] = $arg->value;
            }
        }
        return $propertyFetchesInNewArgs;
    }
    /**
     * @return PropertyFetch[]
     */
    public function findInArrays(Class_ $class, string $propertyName): array
    {
        /** @var Array_[] $arrays */
        $arrays = $this->betterNodeFinder->findInstancesOfScoped($class->getMethods(), Array_::class);
        $propertyFetchesInArrays = [];
        foreach ($arrays as $array) {
            foreach ($array->items as $arrayItem) {
                if (!$arrayItem->value instanceof PropertyFetch) {
                    continue;
                }
                $propertyFetch = $arrayItem->value;
                if (!$this->nodeNameResolver->isName($propertyFetch->name, $propertyName)) {
                    continue;
                }
                $propertyFetchesInArrays[] = $propertyFetch;
            }
        }
        return $propertyFetchesInArrays;
    }
}
