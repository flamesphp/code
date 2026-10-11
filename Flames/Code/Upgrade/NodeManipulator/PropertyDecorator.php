<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class PropertyDecorator
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private StaticTypeMapper $staticTypeMapper, private PhpDocTypeChanger $phpDocTypeChanger)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst $property
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name $typeNode
     */
    public function decorateWithDocBlock($property, $typeNode): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        if ($phpDocInfo->getVarTagValueNode() instanceof VarTagValueNode) {
            return;
        }
        $newType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($typeNode);
        $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $newType);
    }
}
