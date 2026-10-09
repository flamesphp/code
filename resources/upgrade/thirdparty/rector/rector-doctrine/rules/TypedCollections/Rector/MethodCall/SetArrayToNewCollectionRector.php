<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer\CollectionParamCallDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\MethodCall\SetArrayToNewCollectionRectorTest
 */
final class SetArrayToNewCollectionRector extends AbstractRector
{
    public function __construct(private readonly CollectionParamCallDetector $collectionParamCallDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change array to new ArrayCollection() on collection typed property', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\ArrayCollection;

final class SomeClass
{
    /**
     * @var ArrayCollection<int, string>
     */
    public $items;

    public function someMethod()
    {
        $this->items = [];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class SomeClass
{
    /**
     * @var Collection<int, string>
     */
    public $items;

    public function someMethod()
    {
        $this->items = new ArrayCollection([]);
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [MethodCall::class, New_::class, StaticCall::class];
    }
    /**
     * @param MethodCall|New_|StaticCall $node
     * @return \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\New_|\PhpParser\Node\Expr\StaticCall|null
     */
    public function refactor(Node $node)
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getArgs() as $position => $arg) {
            $soleArgType = $this->getType($arg->value);
            if ($soleArgType instanceof ObjectType) {
                continue;
            }
            if (!$this->collectionParamCallDetector->detect($node, $position)) {
                continue;
            }
            $oldArgValue = $arg->value;
            // wrap argument with a collection instance
            $defaultExpr = $this->isName($oldArgValue, 'null') ? new Array_() : $oldArgValue;
            $arg->value = new New_(new FullyQualified(DoctrineClass::ARRAY_COLLECTION), [new Arg($defaultExpr)]);
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
