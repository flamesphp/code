<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Dbal40\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-removed-compositeexpression-methods
 * @see \Flames\Code\Upgrade\Dbal40\Rector\MethodCall\ChangeCompositeExpressionAddMultipleWithWithRectorTest
 */
final class ChangeCompositeExpressionAddMultipleWithWithRector extends AbstractRector implements ComposerPackageConstraintInterface
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
        return new ComposerPackageConstraint('doctrine/dbal', '>=4.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change CompositeExpression ->addMultiple($parts) to ->with(...$parts)', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

class SomeRepository extends EntityRepository
{
    public function getSomething($parts)
    {
        $compositeExpression = CompositeExpression::and('', ...$parts);
        $compositeExpression->addMultiple($parts);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

class SomeRepository extends EntityRepository
{
    public function getSomething($parts)
    {
        $compositeExpression = CompositeExpression::and('', ...$parts);
        $compositeExpression->with(...$parts);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'addMultiple')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->nodeTypeResolver->isObjectType($node->var, new ObjectType(DoctrineClass::COMPOSITE_EXPRESSION))) {
            return null;
        }
        $node->name = new Identifier('with');
        $firstArg = $node->getArgs()[0];
        $firstArg->unpack = \true;
        return $node;
    }
}
