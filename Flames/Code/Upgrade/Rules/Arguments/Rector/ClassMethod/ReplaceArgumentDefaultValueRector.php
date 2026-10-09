<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Arguments\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rules\Arguments\ArgumentDefaultValueReplacer;
use Flames\Code\Upgrade\Rules\Arguments\ValueObject\ReplaceArgumentDefaultValue;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @api used in rector-symfony
 * @see \Flames\Code\Upgrade\Rules\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRectorTest
 */
final class ReplaceArgumentDefaultValueRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ReplaceArgumentDefaultValue[]
     */
    private array $replaceArgumentDefaultValues = [];
    public function __construct(private readonly ArgumentDefaultValueReplacer $argumentDefaultValueReplacer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace defined map of arguments in defined methods and their calls', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod(SomeClass::OLD_CONSTANT);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod(false);
CODE_SAMPLE
, [new ReplaceArgumentDefaultValue('SomeClass', 'someMethod', 0, 'SomeClass::OLD_CONSTANT', \false)])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, ClassMethod::class, New_::class];
    }
    /**
     * @param MethodCall|StaticCall|ClassMethod|New_ $node
     * @return \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Expr\New_|null
     */
    public function refactor(Node $node)
    {
        if ($node instanceof New_) {
            return $this->refactorNew($node);
        }
        $nodeName = $this->getName($node->name);
        if ($nodeName === null) {
            return null;
        }
        $hasChanged = \false;
        $currentNode = $node;
        foreach ($this->replaceArgumentDefaultValues as $replaceArgumentDefaultValue) {
            if (!$this->nodeNameResolver->isStringName($nodeName, $replaceArgumentDefaultValue->getMethod())) {
                continue;
            }
            if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($currentNode, $replaceArgumentDefaultValue->getObjectType())) {
                continue;
            }
            $replacedNode = $this->argumentDefaultValueReplacer->processReplaces($currentNode, $replaceArgumentDefaultValue);
            if ($replacedNode !== null) {
                $currentNode = $replacedNode;
                $hasChanged = \true;
            }
        }
        return $hasChanged ? $currentNode : null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, ReplaceArgumentDefaultValue::class);
        $this->replaceArgumentDefaultValues = $configuration;
    }
    private function refactorNew(New_ $new): ?New_
    {
        $hasChanged = \false;
        $currentNode = $new;
        foreach ($this->replaceArgumentDefaultValues as $replaceArgumentDefaultValue) {
            if ($replaceArgumentDefaultValue->getMethod() !== MethodName::CONSTRUCT) {
                continue;
            }
            if (!$this->isObjectType($currentNode, $replaceArgumentDefaultValue->getObjectType())) {
                continue;
            }
            $replacedNode = $this->argumentDefaultValueReplacer->processReplaces($currentNode, $replaceArgumentDefaultValue);
            if ($replacedNode !== null) {
                $currentNode = $replacedNode;
                $hasChanged = \true;
            }
        }
        return $hasChanged ? $currentNode : null;
    }
}
