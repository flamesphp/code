<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Bridge\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyAttribute;
use Flames\Code\Upgrade\Symfony\TypeAnalyzer\ControllerAnalyzer;
final readonly class ControllerMethodAnalyzer
{
    public function __construct(private ControllerAnalyzer $controllerAnalyzer, private PhpDocInfoFactory $phpDocInfoFactory, private PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    /**
     * Detect if is <some>Action() in Controller
     */
    public function isAction(ClassMethod $classMethod): bool
    {
        if (!$this->controllerAnalyzer->isInsideController($classMethod)) {
            return \false;
        }
        if ($classMethod->isPublic() && !$classMethod->isStatic()) {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
            if ($phpDocInfo instanceof PhpDocInfo && $phpDocInfo->hasByName('required')) {
                return \false;
            }
            return !$this->phpAttributeAnalyzer->hasPhpAttribute($classMethod, SymfonyAttribute::REQUIRED);
        }
        return \false;
    }
}
