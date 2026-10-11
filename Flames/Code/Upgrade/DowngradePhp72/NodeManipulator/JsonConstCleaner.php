<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp72\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\Enum\JsonConstant;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final readonly class JsonConstCleaner
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param array<JsonConstant::*> $constants
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BitwiseOr $node
     */
    public function clean($node, array $constants): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr
    {
        if ($node instanceof BitwiseOr) {
            return $this->cleanByBitwiseOr($node, $constants);
        }
        return $this->cleanByConstFetch($node, $constants);
    }
    /**
     * @param array<JsonConstant::*> $constants
     */
    private function cleanByConstFetch(ConstFetch $constFetch, array $constants): ?Int_
    {
        if (!$this->nodeNameResolver->isNames($constFetch, $constants)) {
            return null;
        }
        return new Int_(0);
    }
    /**
     * @param array<JsonConstant::*> $constants
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_
     */
    private function cleanByBitwiseOr(BitwiseOr $bitwiseOr, array $constants)
    {
        $isLeftTransformed = $this->isTransformed($bitwiseOr->left, $constants);
        $isRightTransformed = $this->isTransformed($bitwiseOr->right, $constants);
        if (!$isLeftTransformed && !$isRightTransformed) {
            return null;
        }
        if (!$isLeftTransformed) {
            return $bitwiseOr->left;
        }
        if (!$isRightTransformed) {
            return $bitwiseOr->right;
        }
        return new Int_(0);
    }
    /**
     * @param string[] $constants
     */
    private function isTransformed(Expr $expr, array $constants): bool
    {
        if ($expr instanceof ConstFetch && $this->nodeNameResolver->isNames($expr, $constants)) {
            return \true;
        }
        return !$expr->getAttribute(AttributeKey::ORIGINAL_NODE) instanceof Node;
    }
}
