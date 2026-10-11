<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rules\DeadCode\NodeCollector\OverriddenParameterResolver;
use Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator\PrivateMethodParamRemover;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveTestsOverriddenPrivateMethodParameterRectorTest
 */
final class RemoveTestsOverriddenPrivateMethodParameterRector extends AbstractRector
{
    public function __construct(private readonly OverriddenParameterResolver $overriddenParameterResolver, private readonly PrivateMethodParamRemover $privateMethodParamRemover, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove parameter of private test class method, that is overridden by direct assign before its first use', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $this->createUser(new User());
    }

    private function createUser($user)
    {
        $user = $this->createMock(User::class);

        return $user;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $this->createUser();
    }

    private function createUser()
    {
        $user = $this->createMock(User::class);

        return $user;
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // narrow scope to test classes for now, as mock overrides are the most common case there
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isPrivate()) {
                continue;
            }
            // constructor is called via new, that is not covered by caller args cleanup
            if ($this->isName($classMethod, MethodName::CONSTRUCT)) {
                continue;
            }
            $overriddenParameters = $this->overriddenParameterResolver->resolve($classMethod);
            if ($overriddenParameters === []) {
                continue;
            }
            if ($this->privateMethodParamRemover->removeParams($node, $classMethod, $overriddenParameters)) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
