<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PropertyTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TemplateTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ThrowsTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\Enum\ClassName;
final class NodeTypes
{
    /**
     * @var array<class-string<PhpDocTagValueNode>>
     */
    public const array TYPE_AWARE_NODES = [VarTagValueNode::class, ParamTagValueNode::class, ReturnTagValueNode::class, ThrowsTagValueNode::class, PropertyTagValueNode::class, TemplateTagValueNode::class];
    /**
     * @var string[]
     */
    public const array TYPE_AWARE_DOCTRINE_ANNOTATION_CLASSES = [ClassName::JMS_TYPE, 'Doctrine\ORM\Mapping\OneToMany', 'Symfony\Component\Validator\Constraints\Choice', 'Symfony\Component\Validator\Constraints\Email', 'Symfony\Component\Validator\Constraints\Range'];
}
