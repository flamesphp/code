<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\DeadReturnTagValueNodeAnalyzer;
final readonly class ReturnTagRemover
{
    public function __construct(private DeadReturnTagValueNodeAnalyzer $deadReturnTagValueNodeAnalyzer)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function removeReturnTagIfUseless(PhpDocInfo $phpDocInfo, $functionLike): bool
    {
        // remove existing type
        $returnTagValueNode = $phpDocInfo->getReturnTagValue();
        if (!$returnTagValueNode instanceof ReturnTagValueNode) {
            return \false;
        }
        $isReturnTagValueDead = $this->deadReturnTagValueNodeAnalyzer->isDead($returnTagValueNode, $functionLike);
        if (!$isReturnTagValueDead) {
            return \false;
        }
        $phpDocInfo->removeByType(ReturnTagValueNode::class);
        return \true;
    }
}
