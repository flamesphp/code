<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\PhpParser\NodeFinder\LocalMethodCallFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\CallTypesResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ClassMethodParamTypeCompleter;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\PrivateMethodFlagger;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRectorTest
 */
final class AddMethodCallBasedStrictParamTypeRector extends AbstractRector
{
    private const int MAX_UNION_TYPES = 3;
    public function __construct(private readonly CallTypesResolver $callTypesResolver, private readonly ClassMethodParamTypeCompleter $classMethodParamTypeCompleter, private readonly LocalMethodCallFinder $localMethodCallFinder, private readonly PrivateMethodFlagger $privateMethodFlagger)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change private method param type to strict type, based on passed strict types', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(int $value)
    {
        $this->resolve($value);
    }

    private function resolve($value)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(int $value)
    {
        $this->resolve($value);
    }

    private function resolve(int $value)
    {
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
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($classMethod->params === []) {
                continue;
            }
            if (!$this->privateMethodFlagger->isClassMethodPrivate($node, $classMethod)) {
                continue;
            }
            if ($classMethod->isPublic()) {
                continue;
            }
            $methodCalls = $this->localMethodCallFinder->match($node, $classMethod);
            $classMethodParameterTypes = $this->callTypesResolver->resolveStrictTypesFromCalls($methodCalls);
            $classMethod = $this->classMethodParamTypeCompleter->complete($classMethod, $classMethodParameterTypes, self::MAX_UNION_TYPES);
            if ($classMethod instanceof ClassMethod) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
