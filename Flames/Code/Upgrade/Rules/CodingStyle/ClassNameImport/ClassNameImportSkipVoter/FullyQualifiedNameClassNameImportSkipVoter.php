<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ClassNameImportSkipVoter;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ShortNameResolver;
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
 * SomeClass::callThis();
 */
final readonly class FullyQualifiedNameClassNameImportSkipVoter implements ClassNameImportSkipVoterInterface
{
    public function __construct(private ShortNameResolver $shortNameResolver)
    {
    }
    public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node): bool
    {
        // "new X" or "X::static()"
        /** @var array<string, string> $shortNamesToFullyQualifiedNames */
        $shortNamesToFullyQualifiedNames = $this->shortNameResolver->resolveFromFile($file);
        $fullyQualifiedObjectTypeShortName = $fullyQualifiedObjectType->getShortName();
        $className = $fullyQualifiedObjectType->getClassName();
        foreach ($shortNamesToFullyQualifiedNames as $shortName => $fullyQualifiedName) {
            if ($fullyQualifiedObjectTypeShortName !== $shortName) {
                $shortName = $this->cleanShortName($shortName);
            }
            if ($fullyQualifiedObjectTypeShortName !== $shortName) {
                continue;
            }
            $fullyQualifiedName = ltrim($fullyQualifiedName, '\\');
            return $className !== $fullyQualifiedName;
        }
        return \false;
    }
    private function cleanShortName(string $shortName): string
    {
        return str_starts_with($shortName, '\\') ? ltrim((string) Strings::after($shortName, '\\', -1)) : $shortName;
    }
}
