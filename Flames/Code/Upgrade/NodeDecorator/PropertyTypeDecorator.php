<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeDecorator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
final readonly class PropertyTypeDecorator
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private PhpVersionProvider $phpVersionProvider, private StaticTypeMapper $staticTypeMapper, private PhpDocTypeChanger $phpDocTypeChanger)
    {
    }
    public function decorate(Property $property, ?Type $type): void
    {
        if (!$type instanceof Type) {
            return;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::TYPED_PROPERTIES)) {
            $phpParserType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PROPERTY);
            if ($phpParserType instanceof Node) {
                $property->type = $phpParserType;
                if ($type instanceof GenericObjectType) {
                    $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $type);
                }
                return;
            }
        }
        $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $type);
    }
}
