<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ClassModifierChecker;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\GetCalledClassToSelfClassRectorTest
 */
final class GetCalledClassToSelfClassRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ClassModifierChecker $classModifierChecker)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `get_called_class()` to `self::class` on final class', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
   public function callOnMe()
   {
       var_dump(get_called_class());
   }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
   public function callOnMe()
   {
       var_dump(self::class);
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
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'get_called_class')) {
            return null;
        }
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        if (!$scope->isInClass()) {
            return null;
        }
        if ($this->classModifierChecker->isInsideFinalClass($node)) {
            return $this->nodeFactory->createClassConstFetch(ObjectReference::SELF, 'class');
        }
        if ($scope->isInAnonymousFunction()) {
            return $this->nodeFactory->createClassConstFetch(ObjectReference::SELF, 'class');
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::CLASSNAME_CONSTANT;
    }
}
