<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder;

use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\ValueObject\DataProviderNodes;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Enum\TestClassName;
final readonly class DataProviderMethodsFinder
{
    /**
     * @var mixed[]
     */
    private const array DATA_PROVIDER_ATTRIBUTES = [TestClassName::PHPUNIT_DATA_PROVIDER, TestClassName::CODECEPTION_DATA_PROVIDER];
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return ClassMethod[]
     */
    public function findDataProviderNodesInClass(Class_ $class): array
    {
        $dataProviderClassMethods = [];
        foreach ($class->getMethods() as $classMethod) {
            $currentDataProviderNodes = $this->findDataProviderNodes($class, $classMethod);
            $dataProviderClassMethods = array_merge($dataProviderClassMethods, $currentDataProviderNodes->getClassMethods());
        }
        return $dataProviderClassMethods;
    }
    public function findDataProviderNodes(Class_ $class, ClassMethod $classMethod): DataProviderNodes
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if ($phpDocInfo instanceof PhpDocInfo) {
            $phpdocNodes = $phpDocInfo->getTagsByName('@dataProvider');
        } else {
            $phpdocNodes = [];
        }
        $attributes = $this->findDataProviderAttributes($classMethod);
        return new DataProviderNodes($class, $attributes, $phpdocNodes);
    }
    /**
     * @return array<Attribute>
     */
    private function findDataProviderAttributes(ClassMethod $classMethod): array
    {
        $dataProviders = [];
        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$this->nodeNameResolver->isNames($attribute->name, self::DATA_PROVIDER_ATTRIBUTES)) {
                    continue;
                }
                $dataProviders[] = $attribute;
            }
        }
        return $dataProviders;
    }
}
