<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\WrapReturn;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\ClassMethod\WrapReturnRectorTest
 */
final class WrapReturnRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var WrapReturn[]
     */
    private array $typeMethodWraps = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Wrap return value of specific method', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function getItem()
    {
        return 1;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function getItem()
    {
        return [1];
    }
}
CODE_SAMPLE
, [new WrapReturn('SomeClass', 'getItem', \true)])]);
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
        $hasChanged = \false;
        foreach ($this->typeMethodWraps as $typeMethodWrap) {
            if (!$this->isObjectType($node, $typeMethodWrap->getObjectType())) {
                continue;
            }
            foreach ($node->getMethods() as $classMethod) {
                if (!$this->isName($classMethod, $typeMethodWrap->getMethod())) {
                    continue;
                }
                if ($node->stmts === null) {
                    continue;
                }
                if ($typeMethodWrap->isArrayWrap() && $this->wrap($classMethod)) {
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
        Assert::allIsAOf($configuration, WrapReturn::class);
        $this->typeMethodWraps = $configuration;
    }
    private function wrap(ClassMethod $classMethod): bool
    {
        if (!is_iterable($classMethod->stmts)) {
            return \false;
        }
        $hasChanged = \false;
        foreach ($classMethod->stmts as $stmt) {
            if ($stmt instanceof Return_ && $stmt->expr instanceof Expr && !$stmt->expr instanceof Array_) {
                $stmt->expr = new Array_([new ArrayItem($stmt->expr)]);
                $hasChanged = \true;
            }
        }
        return $hasChanged;
    }
}
