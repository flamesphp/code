<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Dbal211\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://github.com/doctrine/dbal/pull/3853
 * @changelog https://github.com/doctrine/dbal/issues/3837
 *
 * @see \Flames\Code\Upgrade\Dbal211\Rector\MethodCall\ExtractArrayArgOnQueryBuilderSelectRectorTest
 */
final class ExtractArrayArgOnQueryBuilderSelectRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('doctrine/dbal', '>=2.11');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Extract array arg on QueryBuilder select, addSelect, groupBy, addGroupBy', [new CodeSample(<<<'CODE_SAMPLE'
function query(\Doctrine\DBAL\Query\QueryBuilder $queryBuilder)
{
    $query = $queryBuilder->select(['u.id', 'p.id']);
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function query(\Doctrine\DBAL\Query\QueryBuilder $queryBuilder)
{
    $query = $queryBuilder->select('u.id', 'p.id');
}
CODE_SAMPLE
)]);
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?MethodCall
    {
        if (!$this->isNames($node->name, ['select', 'addSelect', 'groupBy', 'addGroupBy'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $varType = $this->nodeTypeResolver->getType($node->var);
        if (!$varType instanceof ObjectType) {
            return null;
        }
        if (!$varType->isInstanceOf(DoctrineClass::DBAL_QUERY_BUILDER)->yes()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 1) {
            return null;
        }
        $currentArg = $args[0]->value;
        if (!$currentArg instanceof Array_) {
            return null;
        }
        $newArgs = [];
        foreach ($currentArg->items as $value) {
            if (!$value instanceof ArrayItem) {
                return null;
            }
            $newArgs[] = new Arg($value->value);
        }
        $node->args = $newArgs;
        return $node;
    }
}
