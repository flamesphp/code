<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
final class TemplateTypeRemovalGuard
{
    public function isLegal(Type $docType): bool
    {
        // cover direct \PHPStan\Type\Generic\TemplateUnionType
        if ($docType instanceof TemplateType) {
            return \false;
        }
        // cover mixed template with mix from @template and non @template
        $types = $docType instanceof UnionType ? $docType->getTypes() : [$docType];
        $found = array_all($types, fn($type) => !$type instanceof TemplateType);
        return $found;
    }
}
