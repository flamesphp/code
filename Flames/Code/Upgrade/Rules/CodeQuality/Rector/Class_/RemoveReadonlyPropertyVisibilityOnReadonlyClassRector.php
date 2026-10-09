<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\RemoveReadonlyPropertyVisibilityOnReadonlyClassRectorTest
 */
final class RemoveReadonlyPropertyVisibilityOnReadonlyClassRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly VisibilityManipulator $visibilityManipulator)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::READONLY_CLASS;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove readonly property visibility on readonly class', [new CodeSample(<<<'CODE_SAMPLE'
final readonly class SomeClass
{
    public function __construct(
        private readonly string $name
    ) {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final readonly class SomeClass
{
    public function __construct(
        private string $name
    ) {
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
        if (!$node->isReadonly()) {
            return null;
        }
        $hasChanged = \false;
        $constructClassMethod = $node->getMethod(MethodName::CONSTRUCT);
        if ($constructClassMethod instanceof ClassMethod) {
            foreach ($constructClassMethod->getParams() as $param) {
                if (!$param->isReadonly()) {
                    continue;
                }
                $this->visibilityManipulator->removeReadonly($param);
                $hasChanged = \true;
            }
        }
        foreach ($node->getProperties() as $property) {
            if (!$property->isReadonly()) {
                continue;
            }
            $this->visibilityManipulator->removeReadonly($property);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
