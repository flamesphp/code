<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function array_column;
use function array_filter;
use function array_map;
use function implode;
class PhpDocNode implements Node
{
    use NodeAttributes;
    /**
     * @param PhpDocChildNode[] $children
     */
    public function __construct(public array $children)
    {
    }
    /**
     * @return PhpDocTagNode[]
     */
    public function getTags(): array
    {
        return array_filter($this->children, static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode $child): bool => $child instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode);
    }
    /**
     * @return PhpDocTagNode[]
     */
    public function getTagsByName(string $tagName): array
    {
        return array_filter($this->getTags(), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode $tag): bool => $tag->name === $tagName);
    }
    /**
     * @return VarTagValueNode[]
     */
    public function getVarTagValues(string $tagName = '@var'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode);
    }
    /**
     * @return ParamTagValueNode[]
     */
    public function getParamTagValues(string $tagName = '@param'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode);
    }
    /**
     * @return TypelessParamTagValueNode[]
     */
    public function getTypelessParamTagValues(string $tagName = '@param'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TypelessParamTagValueNode);
    }
    /**
     * @return ParamImmediatelyInvokedCallableTagValueNode[]
     */
    public function getParamImmediatelyInvokedCallableTagValues(string $tagName = '@param-immediately-invoked-callable'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamImmediatelyInvokedCallableTagValueNode);
    }
    /**
     * @return ParamLaterInvokedCallableTagValueNode[]
     */
    public function getParamLaterInvokedCallableTagValues(string $tagName = '@param-later-invoked-callable'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamLaterInvokedCallableTagValueNode);
    }
    /**
     * @return ParamClosureThisTagValueNode[]
     */
    public function getParamClosureThisTagValues(string $tagName = '@param-closure-this'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamClosureThisTagValueNode);
    }
    /**
     * @return PureUnlessCallableIsImpureTagValueNode[]
     */
    public function getPureUnlessCallableIsImpureTagValues(string $tagName = '@pure-unless-callable-is-impure'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PureUnlessCallableIsImpureTagValueNode);
    }
    /**
     * @return PureUnlessParameterIsPassedTagValueNode[]
     */
    public function getPureUnlessParameterIsPassedTagValues(string $tagName = '@pure-unless-parameter-passed'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PureUnlessParameterIsPassedTagValueNode);
    }
    /**
     * @return TemplateTagValueNode[]
     */
    public function getTemplateTagValues(string $tagName = '@template'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TemplateTagValueNode);
    }
    /**
     * @return ExtendsTagValueNode[]
     */
    public function getExtendsTagValues(string $tagName = '@extends'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ExtendsTagValueNode);
    }
    /**
     * @return ImplementsTagValueNode[]
     */
    public function getImplementsTagValues(string $tagName = '@implements'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ImplementsTagValueNode);
    }
    /**
     * @return UsesTagValueNode[]
     */
    public function getUsesTagValues(string $tagName = '@use'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\UsesTagValueNode);
    }
    /**
     * @return ReturnTagValueNode[]
     */
    public function getReturnTagValues(string $tagName = '@return'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode);
    }
    /**
     * @return ThrowsTagValueNode[]
     */
    public function getThrowsTagValues(string $tagName = '@throws'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ThrowsTagValueNode);
    }
    /**
     * @return MixinTagValueNode[]
     */
    public function getMixinTagValues(string $tagName = '@mixin'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\MixinTagValueNode);
    }
    /**
     * @return RequireExtendsTagValueNode[]
     */
    public function getRequireExtendsTagValues(string $tagName = '@phpstan-require-extends'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\RequireExtendsTagValueNode);
    }
    /**
     * @return RequireImplementsTagValueNode[]
     */
    public function getRequireImplementsTagValues(string $tagName = '@phpstan-require-implements'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\RequireImplementsTagValueNode);
    }
    /**
     * @return SealedTagValueNode[]
     */
    public function getSealedTagValues(string $tagName = '@phpstan-sealed'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\SealedTagValueNode);
    }
    /**
     * @return DeprecatedTagValueNode[]
     */
    public function getDeprecatedTagValues(): array
    {
        return array_filter(array_column($this->getTagsByName('@deprecated'), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\DeprecatedTagValueNode);
    }
    /**
     * @return PropertyTagValueNode[]
     */
    public function getPropertyTagValues(string $tagName = '@property'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PropertyTagValueNode);
    }
    /**
     * @return PropertyTagValueNode[]
     */
    public function getPropertyReadTagValues(string $tagName = '@property-read'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PropertyTagValueNode);
    }
    /**
     * @return PropertyTagValueNode[]
     */
    public function getPropertyWriteTagValues(string $tagName = '@property-write'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PropertyTagValueNode);
    }
    /**
     * @return MethodTagValueNode[]
     */
    public function getMethodTagValues(string $tagName = '@method'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\MethodTagValueNode);
    }
    /**
     * @return TypeAliasTagValueNode[]
     */
    public function getTypeAliasTagValues(string $tagName = '@phpstan-type'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TypeAliasTagValueNode);
    }
    /**
     * @return TypeAliasImportTagValueNode[]
     */
    public function getTypeAliasImportTagValues(string $tagName = '@phpstan-import-type'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TypeAliasImportTagValueNode);
    }
    /**
     * @return AssertTagValueNode[]
     */
    public function getAssertTagValues(string $tagName = '@phpstan-assert'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\AssertTagValueNode);
    }
    /**
     * @return AssertTagPropertyValueNode[]
     */
    public function getAssertPropertyTagValues(string $tagName = '@phpstan-assert'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\AssertTagPropertyValueNode);
    }
    /**
     * @return AssertTagMethodValueNode[]
     */
    public function getAssertMethodTagValues(string $tagName = '@phpstan-assert'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\AssertTagMethodValueNode);
    }
    /**
     * @return SelfOutTagValueNode[]
     */
    public function getSelfOutTypeTagValues(string $tagName = '@phpstan-this-out'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\SelfOutTagValueNode);
    }
    /**
     * @return ParamOutTagValueNode[]
     */
    public function getParamOutTypeTagValues(string $tagName = '@param-out'): array
    {
        return array_filter(array_column($this->getTagsByName($tagName), 'value'), static fn(\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value): bool => $value instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamOutTagValueNode);
    }
    public function __toString(): string
    {
        $children = array_map(static function (\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode $child): string {
            $s = (string) $child;
            return $s === '' ? '' : ' ' . $s;
        }, $this->children);
        return "/**\n *" . implode("\n *", $children) . "\n */";
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['children']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
