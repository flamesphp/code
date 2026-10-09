<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ClassNameImportSkipVoter;

use PhpParser\Node;
use Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\AliasUsesResolver;
use Flames\Code\Upgrade\Rules\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\ValueObject\Application\File;
/**
 * Prevents adding:
 *
 * use App\SomeClass;
 *
 * If there is already:
 *
 * use App\Something as SomeClass;
 */
final readonly class AliasClassNameImportSkipVoter implements ClassNameImportSkipVoterInterface
{
    public function __construct(private AliasUsesResolver $aliasUsesResolver)
    {
    }
    public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node): bool
    {
        $aliasedUses = $this->aliasUsesResolver->resolveFromNode($node, $file->getNewStmts());
        $longNameLowered = strtolower($fullyQualifiedObjectType->getClassName());
        $shortNameLowered = $fullyQualifiedObjectType->getShortNameLowered();
        foreach ($aliasedUses as $aliasedUse) {
            $aliasedUseLowered = strtolower($aliasedUse);
            // its aliased, we cannot just rename it
            if (str_ends_with($aliasedUseLowered, '\\' . $shortNameLowered)) {
                return \true;
            }
            if ($aliasedUseLowered === $shortNameLowered && $longNameLowered === $shortNameLowered) {
                return \true;
            }
        }
        return \false;
    }
}
