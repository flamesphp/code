<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Attribute;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Reflection\MethodReflection;
use Flames\Code\Upgrade\Rules\CodeQuality\NodeManipulator\NamedArgsSorter;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRectorTest
 */
final class SortAttributeNamedArgsRector extends AbstractRector
{
    public function __construct(private readonly ArgsAnalyzer $argsAnalyzer, private readonly ReflectionResolver $reflectionResolver, private readonly NamedArgsSorter $namedArgsSorter)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Sort named arguments in PHP 8 attributes to match their declaration order', [new CodeSample(<<<'CODE_SAMPLE'
#[SomeAttribute(bar: $bar, foo: $foo)]
class SomeClass
{
}

#[Attribute]
class SomeAttribute
{
    public function __construct(public $foo, public $bar)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
#[SomeAttribute(foo: $foo, bar: $bar)]
class SomeClass
{
}

#[Attribute]
class SomeAttribute
{
    public function __construct(public $foo, public $bar)
    {
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Attribute::class];
    }
    /**
     * @param Node\Attribute $node
     */
    public function refactor(Node $node): ?Node
    {
        $args = $node->args;
        if (count($args) <= 1) {
            return null;
        }
        if (!$this->argsAnalyzer->hasNamedArg($args)) {
            return null;
        }
        $functionLikeReflection = $this->reflectionResolver->resolveConstructorReflectionFromAttribute($node);
        if (!$functionLikeReflection instanceof MethodReflection) {
            return null;
        }
        $args = $this->namedArgsSorter->sortArgsToMatchReflectionParameters($args, $functionLikeReflection);
        if ($node->args === $args) {
            return null;
        }
        $node->args = $args;
        return $node;
    }
}
