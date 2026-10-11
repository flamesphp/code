<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Rector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitorAbstract;
use Flames\Code\Upgrade\ChangesReporting\ValueObject\UpgradeWithLineChange;
use Flames\Code\Upgrade\PostRector\Contract\Rector\PostRectorInterface;
use Flames\Code\Upgrade\ValueObject\Application\File;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
abstract class AbstractPostRector extends NodeVisitorAbstract implements PostRectorInterface
{
    /**
     * @var \Flames\Code\Upgrade\ValueObject\Application\File|null
     */
    private $file = null;
    /**
     * @param Stmt[] $stmts
     */
    public function shouldTraverse(array $stmts): bool
    {
        return \true;
    }
    public function setFile(File $file): void
    {
        $this->file = $file;
    }
    protected function getFile(): File
    {
        Assert::isInstanceOf($this->file, File::class);
        return $this->file;
    }
    protected function addRectorClassWithLine(Node $node): void
    {
        Assert::isInstanceOf($this->file, File::class);
        $rectorWithLineChange = new UpgradeWithLineChange(static::class, $node->getStartLine());
        $this->getFile()->addRectorClassWithLine($rectorWithLineChange);
    }
}
