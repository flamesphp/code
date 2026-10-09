<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ClassNameImportSkipVoter;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\Rules\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\ValueObject\Application\File;
/**
 * This prevents importing:
 * - App\Some\Product
 *
 * if there is already:
 * - use App\Another\Product
 */
final class UsesClassNameImportSkipVoter implements ClassNameImportSkipVoterInterface
{
    public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node): bool
    {
        $fileNode = $file->getFileNode();
        if (!$fileNode instanceof FileNode) {
            return \false;
        }
        $useImportTypes = $fileNode->resolveUsedImportTypes();
        foreach ($useImportTypes as $useImportType) {
            if (!$useImportType->equals($fullyQualifiedObjectType) && $useImportType->areShortNamesEqual($fullyQualifiedObjectType)) {
                return \true;
            }
            if ($useImportType->equals($fullyQualifiedObjectType)) {
                return \false;
            }
        }
        return \false;
    }
}
