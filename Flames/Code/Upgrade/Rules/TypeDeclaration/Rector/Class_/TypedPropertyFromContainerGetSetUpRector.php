<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\SetUpAssignedPropertyTyper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\Class_\TypedPropertyFromContainerGetSetUpRectorTest
 */
final class TypedPropertyFromContainerGetSetUpRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly SetUpAssignedPropertyTyper $setUpAssignedPropertyTyper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add strict typed property to a private test case property based on its @var object type, when assigned via container fetch in setUp()', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @var SomeService
     */
    private $someService;

    protected function setUp(): void
    {
        $this->someService = static::getContainer()->get(SomeService::class);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private SomeService $someService;

    protected function setUp(): void
    {
        $this->someService = static::getContainer()->get(SomeService::class);
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
        return $this->setUpAssignedPropertyTyper->refactorClass($node, fn(Expr $expr): bool => $this->isContainerGetCall($expr));
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    private function isContainerGetCall(Expr $expr): bool
    {
        if (!$expr instanceof MethodCall) {
            return \false;
        }
        if (!$this->isName($expr->name, 'get')) {
            return \false;
        }
        $caller = $expr->var;
        // static::getContainer()->get(...) or $this->getContainer()->get(...)
        if ($caller instanceof StaticCall || $caller instanceof MethodCall) {
            return $this->isName($caller->name, 'getContainer');
        }
        // $this->container->get(...)
        if ($caller instanceof PropertyFetch) {
            return $this->isName($caller, 'container');
        }
        // $this->get(...)
        return $caller instanceof Variable && $this->isName($caller, 'this');
    }
}
