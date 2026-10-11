<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Type;
use function array_key_exists;
use function array_merge;
use function array_shift;
use function count;
use function explode;
use function implode;
use function ltrim;
use function sprintf;
use function str_starts_with;
use function strtolower;
/**
 * @api
 */
final class NameScope
{
    /**
     * @var non-empty-string|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $namespace;
    /**
     * @var array<string, string>
     */
    private array $uses;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $className;
    private ?string $functionName;
    /**
     * @var array<string, TemplateTag>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $templateTags;
    /**
     * @var array<string, true>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $typeAliasesMap;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $bypassTypeAliases;
    /**
     * @var array<string, string>
     */
    private array $constUses;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $typeAliasClassName;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public TemplateTypeMap $templateTypeMap;
    /**
     * @api
     * @param non-empty-string|null $namespace
     * @param array<string, string> $uses alias(string) => fullName(string)
     * @param array<string, string> $constUses alias(string) => fullName(string)
     * @param array<string, TemplateTag> $templateTags
     * @param array<string, true> $typeAliasesMap
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $namespace,
        array $uses,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $className = null,
        ?string $functionName = null,
        ?TemplateTypeMap $templateTypeMap = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $templateTags = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $typeAliasesMap = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $bypassTypeAliases = \false,
        array $constUses = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $typeAliasClassName = null
    )
    {
        $this->namespace = $namespace;
        $this->uses = $uses;
        $this->className = $className;
        $this->functionName = $functionName;
        $this->templateTags = $templateTags;
        $this->typeAliasesMap = $typeAliasesMap;
        $this->bypassTypeAliases = $bypassTypeAliases;
        $this->constUses = $constUses;
        $this->typeAliasClassName = $typeAliasClassName;
        $this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
    }
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }
    /**
     * @return array<string, string>
     */
    public function getUses(): array
    {
        return $this->uses;
    }
    public function hasUseAlias(string $name): bool
    {
        return isset($this->uses[strtolower($name)]);
    }
    /**
     * @return array<string, string>
     */
    public function getConstUses(): array
    {
        return $this->constUses;
    }
    public function getClassName(): ?string
    {
        return $this->className;
    }
    public function getClassNameForTypeAlias(): ?string
    {
        return $this->typeAliasClassName ?? $this->className;
    }
    public function resolveStringName(string $name): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        $nameParts = explode('\\', $name);
        $firstNamePart = strtolower($nameParts[0]);
        if (isset($this->uses[$firstNamePart])) {
            if (count($nameParts) === 1) {
                return $this->uses[$firstNamePart];
            }
            array_shift($nameParts);
            return sprintf('%s\%s', $this->uses[$firstNamePart], implode('\\', $nameParts));
        }
        if ($this->namespace !== null) {
            return sprintf('%s\%s', $this->namespace, $name);
        }
        return $name;
    }
    /**
     * @return non-empty-list<string>
     */
    public function resolveConstantNames(string $name): array
    {
        if (str_starts_with($name, '\\')) {
            return [ltrim($name, '\\')];
        }
        $nameParts = explode('\\', $name);
        $firstNamePart = strtolower($nameParts[0]);
        if (count($nameParts) > 1) {
            if (isset($this->uses[$firstNamePart])) {
                array_shift($nameParts);
                return [sprintf('%s\%s', $this->uses[$firstNamePart], implode('\\', $nameParts))];
            }
        } elseif (isset($this->constUses[$firstNamePart])) {
            return [$this->constUses[$firstNamePart]];
        }
        if ($this->namespace !== null) {
            return [sprintf('%s\%s', $this->namespace, $name), $name];
        }
        return [$name];
    }
    public function getTemplateTypeScope(): ?TemplateTypeScope
    {
        if ($this->className !== null) {
            if ($this->functionName !== null) {
                return TemplateTypeScope::createWithMethod($this->className, $this->functionName);
            }
            return TemplateTypeScope::createWithClass($this->className);
        }
        if ($this->functionName !== null) {
            return TemplateTypeScope::createWithFunction($this->functionName);
        }
        return null;
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        return $this->templateTypeMap;
    }
    /**
     * @return array<string, TemplateTag>
     */
    public function getTemplateTags(): array
    {
        return $this->templateTags;
    }
    public function resolveTemplateTypeName(string $name): ?Type
    {
        return $this->templateTypeMap->getType($name);
    }
    /**
     * @param array<string, TemplateTag> $templateTags
     */
    public function withTemplateTypeMap(TemplateTypeMap $map, array $templateTags): self
    {
        if ($map->isEmpty() && $this->templateTypeMap->isEmpty()) {
            return $this;
        }
        return new self($this->namespace, $this->uses, $this->className, $this->functionName, new TemplateTypeMap(array_merge($this->templateTypeMap->getTypes(), $map->getTypes())), $templateTags, $this->typeAliasesMap, $this->bypassTypeAliases, $this->constUses);
    }
    public function withoutNamespaceAndUses(): self
    {
        return new self(null, [], $this->className, $this->functionName, $this->templateTypeMap, $this->templateTags, $this->typeAliasesMap, $this->bypassTypeAliases, $this->constUses);
    }
    public function withClassName(string $className): self
    {
        return new self($this->namespace, $this->uses, $className, $this->functionName, $this->templateTypeMap, $this->templateTags, $this->typeAliasesMap, $this->bypassTypeAliases, $this->constUses);
    }
    public function unsetTemplateType(string $name): self
    {
        $map = $this->templateTypeMap;
        if (!$map->hasType($name)) {
            return $this;
        }
        return new self($this->namespace, $this->uses, $this->className, $this->functionName, $this->templateTypeMap->unsetType($name), $this->templateTags, $this->typeAliasesMap, $this->bypassTypeAliases, $this->constUses);
    }
    public function bypassTypeAliases(): self
    {
        return new self($this->namespace, $this->uses, $this->className, $this->functionName, $this->templateTypeMap, $this->templateTags, $this->typeAliasesMap, \true, $this->constUses);
    }
    public function shouldBypassTypeAliases(): bool
    {
        return $this->bypassTypeAliases;
    }
    public function hasTypeAlias(string $alias): bool
    {
        return array_key_exists($alias, $this->typeAliasesMap);
    }
}
