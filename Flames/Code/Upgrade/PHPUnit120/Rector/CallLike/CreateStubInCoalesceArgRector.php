<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit120\Rector\CallLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * Related change in PHPUnit 12 https://phpunit.expert/articles/testing-with-and-without-dependencies.html
 *
 * @see \Flames\Code\Upgrade\PHPUnit120\Rector\CallLike\CreateStubInCoalesceArgRectorTest
 */
final class CreateStubInCoalesceArgRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=11.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use createStub() over createMock() when used as argument/array item coalesce ?? fallback', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $mockObject = $this->>get('service');
        $this->someMethod($mockObject ?? $this->createMock(SomeClass::class));
    }

    private function someMethod($someClass)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $mockObject = $this->>get('service');
        $this->someMethod($mockObject ?? $this->createStub(SomeClass::class));
    }

    private function someMethod($someClass)
    {
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class, MethodCall::class, New_::class, ArrayItem::class];
    }
    /**
     * @param MethodCall|StaticCall|New_|ArrayItem $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem|null
     */
    public function refactor(Node $node)
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if ($node instanceof ArrayItem) {
            return $this->refactorArrayItem($node);
        }
        $hasChanges = \false;
        if ($node->isFirstClassCallable()) {
            return null;
        }
        foreach ($node->getArgs() as $arg) {
            if (!$arg->value instanceof Coalesce) {
                continue;
            }
            $coalesce = $arg->value;
            if (!$coalesce->right instanceof MethodCall) {
                continue;
            }
            $methodCall = $coalesce->right;
            if (!$this->isName($methodCall->name, 'createMock')) {
                continue;
            }
            $methodCall->name = new Identifier('createStub');
            $hasChanges = \true;
        }
        if ($hasChanges) {
            return $node;
        }
        return null;
    }
    private function refactorArrayItem(ArrayItem $arrayItem): ?ArrayItem
    {
        if (!$arrayItem->value instanceof Coalesce) {
            return null;
        }
        $coalesce = $arrayItem->value;
        if (!$coalesce->right instanceof MethodCall) {
            return null;
        }
        $methodCall = $coalesce->right;
        if (!$this->isName($methodCall->name, 'createMock')) {
            return null;
        }
        $methodCall->name = new Identifier('createStub');
        return $arrayItem;
    }
}
