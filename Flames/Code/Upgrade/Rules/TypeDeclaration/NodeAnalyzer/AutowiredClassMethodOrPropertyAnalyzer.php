<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
final readonly class AutowiredClassMethodOrPropertyAnalyzer
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    public function matchAutowiredMethodInClass(Class_ $class): ?ClassMethod
    {
        foreach ($class->getMethods() as $classMethod) {
            if (!$classMethod->isPublic()) {
                continue;
            }
            if ($classMethod->isMagic()) {
                continue;
            }
            if (!$this->detect($classMethod)) {
                continue;
            }
            return $classMethod;
        }
        return null;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Param|\PhpParser\Node\Stmt\Property $node
     */
    public function detect($node): bool
    {
        $nodePhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        if ($nodePhpDocInfo->hasByNames(['required', 'inject'])) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttributes($node, ['Symfony\Contracts\Service\Attribute\Required', 'Nette\DI\Attributes\Inject']);
    }
}
