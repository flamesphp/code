<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
final class CoalesceAnalyzer
{
    /**
     * @var array<class-string<Expr>>
     */
    private const array ISSETABLE_EXPR = [Variable::class, ArrayDimFetch::class, PropertyFetch::class, StaticPropertyFetch::class];
    public function hasIssetableLeft(Coalesce $coalesce): bool
    {
        if ($coalesce->left instanceof Coalesce) {
            return \true;
        }
        $leftClass = $coalesce->left::class;
        return in_array($leftClass, self::ISSETABLE_EXPR, \true);
    }
}
