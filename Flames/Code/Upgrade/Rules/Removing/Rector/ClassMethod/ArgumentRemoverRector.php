<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Removing\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArgPlaceholder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Removing\ValueObject\ArgumentRemover;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Removing\Rector\ClassMethod\ArgumentRemoverRectorTest
 */
final class ArgumentRemoverRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var ArgumentRemover[]
     */
    private array $removedArguments = [];
    private bool $hasChanged = \false;
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove defined arguments in defined methods and their calls', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod(true);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod();
CODE_SAMPLE
, [new ArgumentRemover('ExampleClass', 'someMethod', 0, [\true])])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, ClassMethod::class];
    }
    /**
     * @param MethodCall|StaticCall|ClassMethod $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|null
     */
    public function refactor(Node $node)
    {
        $this->hasChanged = \false;
        foreach ($this->removedArguments as $removedArgument) {
            if (!$this->isName($node->name, $removedArgument->getMethod())) {
                continue;
            }
            if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, $removedArgument->getObjectType())) {
                continue;
            }
            $this->processPosition($node, $removedArgument);
        }
        if ($this->hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, ArgumentRemover::class);
        $this->removedArguments = $configuration;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $node
     */
    private function processPosition($node, ArgumentRemover $argumentRemover): void
    {
        if ($argumentRemover->getValue() === null) {
            if ($node instanceof MethodCall || $node instanceof StaticCall) {
                unset($node->args[$argumentRemover->getPosition()]);
            } else {
                unset($node->params[$argumentRemover->getPosition()]);
            }
            return;
        }
        $match = $argumentRemover->getValue();
        if (isset($match['name'])) {
            $this->removeByName($node, $argumentRemover->getPosition(), $match['name']);
            return;
        }
        // only argument specific value can be removed
        if ($node instanceof ClassMethod) {
            return;
        }
        if (!isset($node->args[$argumentRemover->getPosition()])) {
            return;
        }
        if ($this->isArgumentValueMatch($node->args[$argumentRemover->getPosition()], $match)) {
            $this->hasChanged = \true;
            unset($node->args[$argumentRemover->getPosition()]);
        }
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $node
     */
    private function removeByName($node, int $position, string $name): void
    {
        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            if (isset($node->args[$position]) && $this->isName($node->args[$position], $name)) {
                unset($node->args[$position]);
            }
            return;
        }
        if (!isset($node->params[$position]) || !$this->isName($node->params[$position], $name)) {
            return;
        }
        unset($node->params[$position]);
    }
    /**
     * @param mixed[] $values
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArgPlaceholder|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder $arg
     */
    private function isArgumentValueMatch($arg, array $values): bool
    {
        if (!$arg instanceof Arg) {
            return \false;
        }
        $nodeValue = $this->valueResolver->getValue($arg->value);
        return in_array($nodeValue, $values, \true);
    }
}
