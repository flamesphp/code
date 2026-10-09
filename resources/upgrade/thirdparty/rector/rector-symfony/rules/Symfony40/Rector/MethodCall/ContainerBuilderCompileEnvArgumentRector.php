<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony40\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Symfony40\Rector\MethodCall\ContainerBuilderCompileEnvArgumentRectorTest
 */
final class ContainerBuilderCompileEnvArgumentRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/dependency-injection', '>=4.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns old default value to parameter in ContainerBuilder->build() method in DI in Symfony', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->compile();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->compile(true);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'compile')) {
            return null;
        }
        if (count($node->args) === 1) {
            return null;
        }
        if (!$this->isObjectType($node->var, new ObjectType('Symfony\Component\DependencyInjection\ContainerBuilder'))) {
            return null;
        }
        $node->args = $this->nodeFactory->createArgs([$this->nodeFactory->createTrue()]);
        return $node;
    }
}
