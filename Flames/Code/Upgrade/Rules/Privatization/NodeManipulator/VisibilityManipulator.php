<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Privatization\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\Visibility;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\Privatization\NodeManipulator\VisibilityManipulatorTest
 */
final readonly class VisibilityManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function hasVisibility($node, int $visibility): bool
    {
        return (bool) ($node->flags & $visibility);
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function makeStatic($node): void
    {
        $this->addVisibilityFlag($node, Visibility::STATIC);
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property $node
     */
    public function makeNonStatic($node): void
    {
        if (!$node->isStatic()) {
            return;
        }
        $node->flags -= Modifiers::STATIC;
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_ $node
     */
    public function makeNonAbstract($node): void
    {
        if (!$node->isAbstract()) {
            return;
        }
        $node->flags -= Modifiers::ABSTRACT;
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst $node
     */
    public function makeFinal($node): void
    {
        $this->addVisibilityFlag($node, Visibility::FINAL);
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function makeNonFinal($node): void
    {
        if (!$this->hasVisibility($node, Visibility::FINAL)) {
            return;
        }
        $node->flags -= Modifiers::FINAL;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst $node
     */
    public function changeNodeVisibility($node, int $visibility): void
    {
        Assert::oneOf($visibility, [Visibility::PUBLIC, Visibility::PROTECTED, Visibility::PRIVATE, Visibility::STATIC, Visibility::ABSTRACT, Visibility::FINAL]);
        $this->replaceVisibilityFlag($node, $visibility);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function makePublic($node): void
    {
        $this->replaceVisibilityFlag($node, Visibility::PUBLIC);
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst $node
     */
    public function makeProtected($node): void
    {
        $this->replaceVisibilityFlag($node, Visibility::PROTECTED);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function makePrivate($node): void
    {
        $this->replaceVisibilityFlag($node, Visibility::PRIVATE);
        // only constructor can be both private and final
        if ($node instanceof ClassMethod && $this->nodeNameResolver->isName($node, MethodName::CONSTRUCT)) {
            return;
        }
        $node->flags &= ~Modifiers::FINAL;
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst $node
     */
    public function removeFinal($node): void
    {
        $node->flags -= Modifiers::FINAL;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function makeReadonly($node): void
    {
        $this->addVisibilityFlag($node, Visibility::READONLY);
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function isReadonly($node): bool
    {
        return $this->hasVisibility($node, Visibility::READONLY);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function removeReadonly($node): void
    {
        $isConstructorPromotionBefore = $node instanceof Param && $node->isPromoted();
        $node->flags &= ~Modifiers::READONLY;
        $isConstructorPromotionAfter = $node instanceof Param && $node->isPromoted();
        if ($node instanceof Param && $isConstructorPromotionBefore && !$isConstructorPromotionAfter) {
            $this->makePublic($node);
        }
        if ($node instanceof Property) {
            $this->publicize($node);
        }
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|null
     */
    public function publicize($node)
    {
        // already non-public
        if (!$node->isPublic()) {
            return null;
        }
        // explicitly public
        if ($this->hasVisibility($node, Visibility::PUBLIC)) {
            return null;
        }
        $this->makePublic($node);
        return $node;
    }
    /**
     * This way "abstract", "static", "final" are kept
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    private function removeVisibility($node): void
    {
        // no modifier
        if ($node->flags === 0) {
            return;
        }
        if ($node->isPublic()) {
            $node->flags |= Modifiers::PUBLIC;
            $node->flags -= Modifiers::PUBLIC;
        }
        if ($node->isProtected()) {
            $node->flags -= Modifiers::PROTECTED;
        }
        if ($node->isPrivate()) {
            $node->flags -= Modifiers::PRIVATE;
        }
    }
    /**
     * @api
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    private function addVisibilityFlag($node, int $visibility): void
    {
        $node->flags |= $visibility;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    private function replaceVisibilityFlag($node, int $visibility): void
    {
        $isStatic = $node instanceof ClassMethod && $node->isStatic();
        if ($isStatic) {
            $this->makeNonStatic($node);
        }
        if (!in_array($visibility, [Visibility::STATIC, Visibility::ABSTRACT, Visibility::FINAL], \true)) {
            $this->removeVisibility($node);
        }
        $this->addVisibilityFlag($node, $visibility);
        if ($isStatic) {
            $this->makeStatic($node);
        }
    }
}
