<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Rector\ClassConstFetch;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * ::class introduced in php 5.5
 * while $this::class introduced in php 8.0
 *
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\ClassConstFetch\ClassOnThisVariableObjectRectorTest
 */
final class ClassOnThisVariableObjectRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change $this::class to static::class or self::class depends on class modifier', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return $this::class;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return static::class;
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
        $className = $node->isFinal() ? 'self' : 'static';
        $hasChanged = \false;
        $this->traverseNodesWithCallable($node, function (Node $node) use (&$hasChanged, $className): ?ClassConstFetch {
            if (!$node instanceof ClassConstFetch) {
                return null;
            }
            if ($this->shouldSkip($node)) {
                return null;
            }
            $node->class = new Name($className);
            $hasChanged = \true;
            return $node;
        });
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::CLASS_ON_OBJECT;
    }
    private function shouldSkip(ClassConstFetch $classConstFetch): bool
    {
        if (!$classConstFetch->class instanceof Variable) {
            return \true;
        }
        if (!is_string($classConstFetch->class->name)) {
            return \true;
        }
        if (!$this->isName($classConstFetch->class, 'this')) {
            return \true;
        }
        if (!$classConstFetch->name instanceof Identifier) {
            return \true;
        }
        return !$this->isName($classConstFetch->name, 'class');
    }
}
