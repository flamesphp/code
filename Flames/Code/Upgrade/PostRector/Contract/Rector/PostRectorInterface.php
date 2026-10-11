<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Contract\Rector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\ValueObject\Application\File;
/**
 * @internal
 */
interface PostRectorInterface extends NodeVisitor
{
    /**
     * @param Stmt[] $stmts
     */
    public function shouldTraverse(array $stmts): bool;
    public function setFile(File $file): void;
}
