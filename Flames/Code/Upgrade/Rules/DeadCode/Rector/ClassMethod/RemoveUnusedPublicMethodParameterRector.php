<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Configuration\Parameter\FeatureFlags;
use Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator\ClassMethodParamRemover;
use Flames\Code\Upgrade\NodeAnalyzer\MagicClassMethodAnalyzer;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRectorTest
 */
final class RemoveUnusedPublicMethodParameterRector extends AbstractRector
{
    public function __construct(private readonly ClassMethodParamRemover $classMethodParamRemover, private readonly MagicClassMethodAnalyzer $magicClassMethodAnalyzer, private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused parameter in public method on final class without extends and interface', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($a, $b)
    {
        echo $a;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($a)
    {
        echo $a;
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
        // may have child, or override parent that needs to follow the signature
        if (!$node->isFinal() && FeatureFlags::treatClassesAsFinal($node) === \false) {
            return null;
        }
        if ($node->extends instanceof FullyQualified || $node->implements !== []) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($this->shouldSkipClassMethod($classMethod, $node)) {
                continue;
            }
            $changedMethod = $this->classMethodParamRemover->processRemoveParams($classMethod);
            if (!$changedMethod instanceof ClassMethod) {
                continue;
            }
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function shouldSkipClassMethod(ClassMethod $classMethod, Class_ $class): bool
    {
        // private method is handled by different rule
        if (!$classMethod->isPublic()) {
            return \true;
        }
        if ($classMethod->params === []) {
            return \true;
        }
        // parameter is required for contract coupling
        if ($this->isName($classMethod->name, MethodName::INVOKE) && $this->phpAttributeAnalyzer->hasPhpAttribute($class, 'Symfony\Component\Messenger\Attribute\AsMessageHandler')) {
            return \true;
        }
        return $this->magicClassMethodAnalyzer->isUnsafeOverridden($classMethod);
    }
}
