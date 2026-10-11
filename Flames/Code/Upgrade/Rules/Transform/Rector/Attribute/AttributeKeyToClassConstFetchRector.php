<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\Attribute;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\AttributeKeyToClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @api used in rector-doctrine
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRectorTest
 */
final class AttributeKeyToClassConstFetchRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var AttributeKeyToClassConstFetch[]
     */
    private array $attributeKeysToClassConstFetches = [];
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace key value on specific attribute to class constant', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping\Column;

class SomeClass
{
    #[Column(type: "string")]
    public $name;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping\Column;
use Doctrine\DBAL\Types\Types;

class SomeClass
{
    #[Column(type: Types::STRING)]
    public $name;
}
CODE_SAMPLE
, [new AttributeKeyToClassConstFetch('Doctrine\ORM\Mapping\Column', 'type', 'Doctrine\DBAL\Types\Types', ['string' => 'STRING'])])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, Param::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class, Interface_::class];
    }
    /**
     * @param Class_|Property|Param|ClassMethod|Function_|Closure|ArrowFunction|Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->attrGroups === []) {
            return null;
        }
        $hasChanged = \false;
        foreach ($this->attributeKeysToClassConstFetches as $attributeKeyToClassConstFetch) {
            foreach ($node->attrGroups as $attrGroup) {
                if ($this->processToClassConstFetch($attrGroup, $attributeKeyToClassConstFetch)) {
                    $hasChanged = \true;
                }
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
        Assert::allIsAOf($configuration, AttributeKeyToClassConstFetch::class);
        $this->attributeKeysToClassConstFetches = $configuration;
    }
    private function processToClassConstFetch(AttributeGroup $attributeGroup, AttributeKeyToClassConstFetch $attributeKeyToClassConstFetch): bool
    {
        $hasChanged = \false;
        foreach ($attributeGroup->attrs as $attribute) {
            if (!$this->isName($attribute->name, $attributeKeyToClassConstFetch->getAttributeClass())) {
                continue;
            }
            foreach ($attribute->args as $arg) {
                $argName = $arg->name;
                if (!$argName instanceof Identifier) {
                    continue;
                }
                if (!$this->isName($argName, $attributeKeyToClassConstFetch->getAttributeKey())) {
                    continue;
                }
                if ($this->processArg($arg, $attributeKeyToClassConstFetch)) {
                    $hasChanged = \true;
                }
            }
        }
        return $hasChanged;
    }
    private function processArg(Arg $arg, AttributeKeyToClassConstFetch $attributeKeyToClassConstFetch): bool
    {
        $value = $this->valueResolver->getValue($arg->value);
        $constName = $attributeKeyToClassConstFetch->getValuesToConstantsMap()[$value] ?? null;
        if ($constName === null) {
            return \false;
        }
        $classConstFetch = $this->nodeFactory->createClassConstFetch($attributeKeyToClassConstFetch->getConstantClass(), $constName);
        if ($arg->value instanceof ClassConstFetch && $this->getName($arg->value) === $this->getName($classConstFetch)) {
            return \false;
        }
        $arg->value = $classConstFetch;
        return \true;
    }
}
