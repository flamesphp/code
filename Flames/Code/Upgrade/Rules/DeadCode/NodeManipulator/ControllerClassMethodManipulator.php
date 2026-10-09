<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\SpacelessPhpDocTagNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ControllerClassMethodManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function isControllerClassMethod(Class_ $class, ClassMethod $classMethod): bool
    {
        if (!$classMethod->isPublic()) {
            return \false;
        }
        if (!$this->hasParentClassController($class)) {
            return \false;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        return $phpDocInfo->hasByTypes([GenericTagValueNode::class, SpacelessPhpDocTagNode::class]);
    }
    private function hasParentClassController(Class_ $class): bool
    {
        if (!$class->extends instanceof Name) {
            return \false;
        }
        $parentClassName = $this->nodeNameResolver->getName($class->extends);
        if (str_ends_with($parentClassName, 'Controller')) {
            return \true;
        }
        return str_ends_with($parentClassName, 'Presenter');
    }
}
