<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\UseAliasVisitor;
use PHPStan\Reflection\ReflectionProvider;
use function count;
use function implode;
use function sprintf;
use function strtolower;
#[AutowiredService]
final class ClassCaseSensitivityCheck
{
    private ReflectionProvider $reflectionProvider;
    private bool $checkInternalClassCaseSensitivity;
    private bool $checkImportedClassNameCase;
    public function __construct(
        ReflectionProvider $reflectionProvider,
        #[AutowiredParameter]
        bool $checkInternalClassCaseSensitivity,
        #[AutowiredParameter('%featureToggles.checkImportedClassNameCase%')]
        bool $checkImportedClassNameCase
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->checkInternalClassCaseSensitivity = $checkInternalClassCaseSensitivity;
        $this->checkImportedClassNameCase = $checkImportedClassNameCase;
    }
    /**
     * @param ClassNameNodePair[] $pairs
     * @return list<IdentifierRuleError>
     */
    public function checkClassNames(array $pairs): array
    {
        $errors = [];
        foreach ($pairs as $pair) {
            $className = $this->getClassNameAsWritten($pair);
            if (!$this->reflectionProvider->hasClass($className)) {
                continue;
            }
            $classReflection = $this->reflectionProvider->getClass($className);
            if (!$this->checkInternalClassCaseSensitivity && $classReflection->isBuiltin()) {
                continue;
                // skip built-in classes
            }
            $realClassName = $classReflection->getName();
            if (strtolower($realClassName) !== strtolower($className)) {
                continue;
                // skip class_alias() where the alias is a completely different name
            }
            if ($pair->node->getAttribute(UseAliasVisitor::ATTRIBUTE_NAME) === \true) {
                continue;
            }
            if ($realClassName === $className) {
                continue;
            }
            $typeName = $classReflection->getClassTypeDescription();
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('%s %s referenced with incorrect case: %s.', $typeName, $realClassName, $className))->identifier(sprintf('%s.nameCase', strtolower($typeName)))->line($pair->node->getStartLine())->build();
        }
        return $errors;
    }
    /**
     * @return non-empty-string
     */
    private function getClassNameAsWritten(\PHPStan\Rules\ClassNameNodePair $pair): string
    {
        $className = $pair->className;
        if (!$this->checkImportedClassNameCase) {
            return $className;
        }
        $node = $pair->node;
        if (!$node instanceof Name || $node->name !== $className) {
            return $className;
        }
        return self::getNameAsWritten($node);
    }
    /**
     * NameResolver replaces a single-part imported name entirely with the use statement's
     * spelling, so the case the user wrote survives only in the originalName attribute.
     * Multi-part names keep the written case of everything after the first part.
     *
     * @return non-empty-string
     */
    public static function getNameAsWritten(Name $node): string
    {
        $resolvedName = $node->name;
        $originalName = $node->getAttribute('originalName');
        if (!$originalName instanceof Name || $originalName instanceof Name\FullyQualified || $originalName instanceof Name\Relative) {
            return $resolvedName;
        }
        $originalParts = \explode('\\', $originalName->name);
        if (count($originalParts) !== 1) {
            return $resolvedName;
        }
        $resolvedParts = \explode('\\', $node->name);
        $resolvedParts[count($resolvedParts) - 1] = $originalParts[0];
        return implode('\\', $resolvedParts);
    }
}
