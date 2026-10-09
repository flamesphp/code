<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ClassNameImportSkipVoter;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Rules\CodingStyle\Contract\ClassNameImport\ClassNameImportSkipVoterInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\ValueObject\Application\File;
final class OriginalNameImportSkipVoter implements ClassNameImportSkipVoterInterface
{
    public function shouldSkip(File $file, FullyQualifiedObjectType $fullyQualifiedObjectType, Node $node): bool
    {
        if (!$node instanceof FullyQualified) {
            return \false;
        }
        if (substr_count($node->toCodeString(), '\\') === 1) {
            return \false;
        }
        // verify long name, as short name verify may conflict
        // see test PR: https://github.com/rectorphp/rector-src/pull/6208
        // ref https://3v4l.org/21H5j vs https://3v4l.org/GIHSB
        $originalName = $node->getAttribute(AttributeKey::ORIGINAL_NAME);
        return $originalName instanceof Name && $originalName->getLast() === $originalName->toString();
    }
}
