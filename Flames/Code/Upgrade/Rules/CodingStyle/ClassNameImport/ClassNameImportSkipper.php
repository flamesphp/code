<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\GroupUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
use Flames\Code\Upgrade\Rules\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\ValueObject\Application\File;
final readonly class ClassNameImportSkipper
{
    /**
     * @param ClassNameImportSkipVoterInterface[] $classNameImportSkipVoters
     */
    public function __construct(private array $classNameImportSkipVoters, private UseImportsResolver $useImportsResolver)
    {
    }
    public function shouldSkipNameForFullyQualifiedObjectType(File $file, Node $node, FullyQualifiedObjectType $fullyQualifiedObjectType): bool
    {
        $found = array_any($this->classNameImportSkipVoters, fn($classNameImportSkipVoter) => $classNameImportSkipVoter->shouldSkip($file, $fullyQualifiedObjectType, $node));
        return $found;
    }
    /**
     * @param array<Use_|GroupUse> $uses
     */
    public function shouldSkipName(FullyQualified $fullyQualified, array $uses): bool
    {
        if (substr_count($fullyQualified->toCodeString(), '\\') === 1) {
            return $this->isFunctionOrConstantImport($fullyQualified);
        }
        $stringName = $fullyQualified->toString();
        $lastUseName = $fullyQualified->getLast();
        $nameLastName = strtolower($lastUseName);
        foreach ($uses as $use) {
            $prefix = $this->useImportsResolver->resolvePrefix($use);
            $useName = $prefix . $stringName;
            foreach ($use->uses as $useUse) {
                $useUseLastName = strtolower($useUse->name->getLast());
                if ($useUseLastName !== $nameLastName) {
                    continue;
                }
                if ($this->isConflictedShortNameInUse($useUse, $useName, $lastUseName, $stringName)) {
                    return \true;
                }
                return $prefix . $useUse->name->toString() !== $stringName;
            }
        }
        return \false;
    }
    private function isFunctionOrConstantImport(FullyQualified $fullyQualified): bool
    {
        if ($fullyQualified->getAttribute(AttributeKey::IS_CONSTFETCH_NAME) === \true) {
            return \true;
        }
        return $fullyQualified->getAttribute(AttributeKey::IS_FUNCCALL_NAME) === \true;
    }
    private function isConflictedShortNameInUse(UseItem $useItem, string $useName, string $lastUseName, string $stringName): bool
    {
        if (!$useItem->alias instanceof Identifier && $useName !== $stringName && $lastUseName === $stringName) {
            return \true;
        }
        return $useItem->alias instanceof Identifier && $useItem->alias->toString() === $stringName;
    }
}
