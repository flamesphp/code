<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
final class GenericTypeNodeAnalyzer
{
    public function hasGenericType(BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): bool
    {
        $types = $bracketsAwareUnionTypeNode->types;
        $found = array_any($types, fn($typeNode) => $typeNode instanceof GenericTypeNode);
        return $found;
    }
}
