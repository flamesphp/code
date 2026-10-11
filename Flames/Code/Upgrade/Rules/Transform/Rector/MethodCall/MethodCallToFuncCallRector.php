<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\MethodCallToFuncCall;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @note used extensively https://github.com/search?q=MethodCallToFuncCallRector%3A%3Aclass&type=code
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\MethodCall\MethodCallToFuncCallRectorTest
 */
final class MethodCallToFuncCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var MethodCallToFuncCall[]
     */
    private array $methodCallsToFuncCalls = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change method call to function call', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function show()
    {
        return $this->render('some_template');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function show()
    {
        return view('some_template');
    }
}
CODE_SAMPLE
, [new MethodCallToFuncCall('SomeClass', 'render', 'view')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        foreach ($this->methodCallsToFuncCalls as $methodCallToFuncCall) {
            if (!$this->isName($node->name, $methodCallToFuncCall->getMethodName())) {
                continue;
            }
            if (!$this->isObjectType($node->var, new ObjectType($methodCallToFuncCall->getObjectType()))) {
                continue;
            }
            return new FuncCall(new FullyQualified($methodCallToFuncCall->getFunctionName()), $node->getArgs());
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, MethodCallToFuncCall::class);
        $this->methodCallsToFuncCalls = $configuration;
    }
}
