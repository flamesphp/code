<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
final class IntermediaryNameScope
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\Analyser\NamespaceUses $namespaceUses;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $className;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $functionName;
    /**
     * @var array<string, array{string, TemplateTagValueNode}>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $templatePhpDocNodes;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?self $parent;
    /**
     * @var array<string, true>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $typeAliasesMap;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $bypassTypeAliases;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $typeAliasClassName;
    /**
     * @var array{string, string, string, string|null, string|null}|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?array $traitData;
    /**
     * @param array<string, array{string, TemplateTagValueNode}> $templatePhpDocNodes
     * @param array<string, true> $typeAliasesMap
     * @param array{string, string, string, string|null, string|null}|null $traitData
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\Analyser\NamespaceUses $namespaceUses,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $className = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $functionName = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $templatePhpDocNodes = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?self $parent = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $typeAliasesMap = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $bypassTypeAliases = \false,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $typeAliasClassName = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?array $traitData = null
    )
    {
        $this->namespaceUses = $namespaceUses;
        $this->className = $className;
        $this->functionName = $functionName;
        $this->templatePhpDocNodes = $templatePhpDocNodes;
        $this->parent = $parent;
        $this->typeAliasesMap = $typeAliasesMap;
        $this->bypassTypeAliases = $bypassTypeAliases;
        $this->typeAliasClassName = $typeAliasClassName;
        $this->traitData = $traitData;
    }
    /**
     * @return non-empty-string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespaceUses->namespace;
    }
    /**
     * @return array<string, string>
     */
    public function getUses(): array
    {
        return $this->namespaceUses->uses;
    }
    /**
     * @return array<string, string>
     */
    public function getConstUses(): array
    {
        return $this->namespaceUses->constUses;
    }
    public function getClassName(): ?string
    {
        return $this->className;
    }
    public function getFunctionName(): ?string
    {
        return $this->functionName;
    }
    /**
     * @return array<string, array{string, TemplateTagValueNode}>
     */
    public function getTemplatePhpDocNodes(): array
    {
        return $this->templatePhpDocNodes;
    }
    public function withTraitData(string $fileName, string $className, string $traitName, ?string $lookForTraitName, ?string $docComment): self
    {
        return new self($this->namespaceUses, $this->className, $this->functionName, $this->templatePhpDocNodes, $this->parent, $this->typeAliasesMap, $this->bypassTypeAliases, $this->typeAliasClassName, [$fileName, $className, $traitName, $lookForTraitName, $docComment]);
    }
    /**
     * @param string[] $namesToUnset
     */
    public function unsetTemplatePhpDocNodes(array $namesToUnset): self
    {
        $templatePhpDocNodes = $this->templatePhpDocNodes;
        foreach ($namesToUnset as $name) {
            unset($templatePhpDocNodes[$name]);
        }
        return new self($this->namespaceUses, $this->className, $this->functionName, $templatePhpDocNodes, $this->parent, $this->typeAliasesMap, $this->bypassTypeAliases, $this->typeAliasClassName, $this->traitData);
    }
    /**
     * @return array{string, string, string, string|null, string|null}|null
     */
    public function getTraitData(): ?array
    {
        return $this->traitData;
    }
    public function getParent(): ?self
    {
        return $this->parent;
    }
    /**
     * @return array<string, true>
     */
    public function getTypeAliasesMap(): array
    {
        return $this->typeAliasesMap;
    }
    public function shouldBypassTypeAliases(): bool
    {
        return $this->bypassTypeAliases;
    }
    public function getClassNameForTypeAlias(): ?string
    {
        return $this->typeAliasClassName;
    }
}
