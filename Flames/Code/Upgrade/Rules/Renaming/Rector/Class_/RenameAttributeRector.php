<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Renaming\ValueObject\RenameAttribute;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Renaming\Rector\Class_\RenameAttributeRectorTest
 */
final class RenameAttributeRector extends AbstractRector implements ConfigurableRectorInterface, MinPhpVersionInterface
{
    /**
     * @var RenameAttribute[]
     */
    private array $renameAttributes = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename attribute class names', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
#[SimpleRoute()]
class SomeClass
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
#[BasicRoute()]
class SomeClass
{
}
CODE_SAMPLE
, [new RenameAttribute('SimpleRoute', 'BasicRoute')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, ClassMethod::class, Property::class, Param::class];
    }
    /**
     * @param Class_|ClassMethod|Property|Param $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $newAttributeName = $this->matchNewAttributeName($attr);
                if (!is_string($newAttributeName)) {
                    continue;
                }
                $attr->name = new FullyQualified($newAttributeName);
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameAttribute::class);
        $this->renameAttributes = $configuration;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    private function matchNewAttributeName(Attribute $attribute): ?string
    {
        foreach ($this->renameAttributes as $renameAttribute) {
            if ($this->isName($attribute->name, $renameAttribute->getOldAttribute())) {
                return $renameAttribute->getNewAttribute();
            }
        }
        return null;
    }
}
