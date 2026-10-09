<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover\VarTagRemover;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Property\RemoveUselessVarTagRectorTest
 */
final class RemoveUselessVarTagRector extends AbstractRector
{
    public function __construct(private readonly VarTagRemover $varTagRemover, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused `@var` annotation for properties and class constants', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var string
     */
    public string $name = 'name';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public string $name = 'name';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class, ClassConst::class, Expression::class];
    }
    /**
     * @param Property|ClassConst|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $hasChanged = $this->varTagRemover->removeVarTagIfUseless($phpDocInfo, $node);
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
