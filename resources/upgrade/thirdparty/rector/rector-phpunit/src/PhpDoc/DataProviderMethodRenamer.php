<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\PhpDoc;

use FlamesPrefix202610\Nette\Utils\Strings;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
final readonly class DataProviderMethodRenamer
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private DocBlockUpdater $docBlockUpdater)
    {
    }
    public function removeTestPrefix(Class_ $class): void
    {
        foreach ($class->getMethods() as $classMethod) {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
            $hasClassMethodChanged = \false;
            foreach ($phpDocInfo->getTagsByName('dataProvider') as $phpDocTagNode) {
                if (!$phpDocTagNode->value instanceof GenericTagValueNode) {
                    continue;
                }
                $oldMethodName = $phpDocTagNode->value->value;
                if (!str_starts_with($oldMethodName, 'test')) {
                    continue;
                }
                $newMethodName = $this->createMethodNameWithoutPrefix($oldMethodName, 'test');
                $phpDocTagNode->value->value = Strings::replace($oldMethodName, '#' . preg_quote($oldMethodName, '#') . '#', $newMethodName);
                // invoke reprint
                $phpDocTagNode->setAttribute(PhpDocAttributeKey::START_AND_END, null);
                $hasClassMethodChanged = \true;
            }
            if ($hasClassMethodChanged) {
                $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($classMethod);
            }
        }
    }
    private function createMethodNameWithoutPrefix(string $methodName, string $prefix): string
    {
        $newMethodName = Strings::substring($methodName, strlen($prefix));
        return lcfirst($newMethodName);
    }
}
