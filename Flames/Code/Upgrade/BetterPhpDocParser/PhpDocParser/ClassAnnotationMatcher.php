<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\GroupUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Rules\CodingStyle\NodeAnalyzer\UseImportNameMatcher;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
/**
 * Matches "@ORM\Entity" to FQN names based on use imports in the file
 */
final class ClassAnnotationMatcher
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $fullyQualifiedNameByHash = [];
    public function __construct(private readonly UseImportNameMatcher $useImportNameMatcher, private readonly UseImportsResolver $useImportsResolver, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    /**
     * @return non-empty-string
     */
    public function resolveTagFullyQualifiedName(string $tag, Node $node): string
    {
        $uniqueId = $tag . spl_object_id($node);
        if (isset($this->fullyQualifiedNameByHash[$uniqueId])) {
            return $this->fullyQualifiedNameByHash[$uniqueId];
        }
        $tag = ltrim($tag, '@');
        if ($tag === '') {
            throw new ShouldNotHappenException();
        }
        $uses = $this->useImportsResolver->resolve();
        $fullyQualifiedClass = $this->resolveFullyQualifiedClass($uses, $node, $tag);
        $fullyQualifiedClass ??= $tag;
        $this->fullyQualifiedNameByHash[$uniqueId] = $fullyQualifiedClass;
        return $fullyQualifiedClass;
    }
    /**
     * @param array<Use_|GroupUse> $uses
     * @return non-empty-string|null
     */
    private function resolveFullyQualifiedClass(array $uses, Node $node, string $tag): ?string
    {
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if ($scope instanceof Scope) {
            $namespace = $scope->getNamespace();
            if ($namespace !== null) {
                $namespacedTag = $namespace . '\\' . $tag;
                if ($this->reflectionProvider->hasClass($namespacedTag)) {
                    return $namespacedTag;
                }
                if (!str_contains($tag, '\\')) {
                    return $this->resolveAsAliased($uses, $tag);
                }
                if ($this->isPreslashedExistingClass($tag)) {
                    // Global or absolute Class
                    return $tag;
                }
            }
        }
        return $this->useImportNameMatcher->matchNameWithUses($tag, $uses);
    }
    /**
     * @param array<Use_|GroupUse> $uses
     * @return non-empty-string|null
     */
    private function resolveAsAliased(array $uses, string $tag): ?string
    {
        foreach ($uses as $use) {
            $prefix = $this->useImportsResolver->resolvePrefix($use);
            foreach ($use->uses as $useUse) {
                if (!$useUse->alias instanceof Identifier) {
                    continue;
                }
                if ($useUse->alias->toString() === $tag) {
                    return $prefix . $useUse->name->toString();
                }
            }
        }
        return $this->useImportNameMatcher->matchNameWithUses($tag, $uses);
    }
    private function isPreslashedExistingClass(string $tag): bool
    {
        if (!str_starts_with($tag, '\\')) {
            return \false;
        }
        return $this->reflectionProvider->hasClass($tag);
    }
}
