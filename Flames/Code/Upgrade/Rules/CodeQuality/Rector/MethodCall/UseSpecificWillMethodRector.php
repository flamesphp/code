<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://github.com/symfony/symfony/pull/29685/files
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\UseSpecificWillMethodRectorTest
 */
final class UseSpecificWillMethodRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const array NESTED_METHOD_TO_RENAME_MAP = ['returnArgument' => 'willReturnArgument', 'returnCallback' => 'willReturnCallback', 'returnSelf' => 'willReturnSelf', 'returnValue' => 'willReturn', 'returnValueMap' => 'willReturnMap', 'onConsecutiveCalls' => 'willReturnOnConsecutiveCalls', 'throwException' => 'willThrowException'];
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes $mock->will() call to more specific method', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass extends PHPUnit\Framework\TestCase
{
    public function test()
    {
        $translator = $this->createMock('SomeClass');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValue('translated max {{ max }}!'));
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass extends PHPUnit\Framework\TestCase
{
    public function test()
    {
        $translator = $this->createMock('SomeClass');
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnValue('translated max {{ max }}!');
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }
    /**
     * @param MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        // we cannot check caller types, as on old PHPUnit version, this the magic ->method() call result to a mixed type
        if (!$this->isName($node->name, 'will')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        // special case for new map
        if ($firstArg->value instanceof New_) {
            return $this->refactorNew($firstArg->value, $node);
        }
        if (!$firstArg->value instanceof MethodCall && !$firstArg->value instanceof StaticCall) {
            return null;
        }
        $nestedMethodCall = $firstArg->value;
        foreach (self::NESTED_METHOD_TO_RENAME_MAP as $oldMethodName => $newParentMethodName) {
            if (!$this->isName($nestedMethodCall->name, $oldMethodName)) {
                continue;
            }
            $node->name = new Identifier($newParentMethodName);
            $node->args = $nestedMethodCall->args;
            return $node;
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $call
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    private function refactorNew(New_ $new, $call)
    {
        if (!$this->isName($new->class, 'PHPUnit\Framework\MockObject\Stub\ReturnValueMap')) {
            return null;
        }
        $call->name = new Identifier('willReturnMap');
        $call->args = $new->args;
        return $call;
    }
}
