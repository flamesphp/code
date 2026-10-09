<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony51\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
use Flames\Code\Upgrade\Symfony\ValueObject\ConstantMap\SymfonyCommandConstantMap;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * https://symfony.com/blog/new-in-symfony-5-1-misc-improvements-part-1#added-constants-for-command-exit-codes
 *
 * @see \Flames\Code\Upgrade\Symfony51\Rector\ClassMethod\CommandConstantReturnCodeRectorTest
 */
final class CommandConstantReturnCodeRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/console', '>=5.1');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes int return from execute to use Symfony Command constants.', [new CodeSample(<<<'CODE_SAMPLE'
class SomeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }

}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return \Symfony\Component\Console\Command\Command::SUCCESS;
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        if (!$classReflection->is(SymfonyClass::COMMAND)) {
            return null;
        }
        if (!$this->isName($node, 'execute')) {
            return null;
        }
        $hasChanged = \false;
        /** @var Return_[] $returns */
        $returns = $this->betterNodeFinder->findInstancesOfInFunctionLikeScoped($node, [Return_::class]);
        foreach ($returns as $return) {
            if (!$return->expr instanceof Int_) {
                continue;
            }
            $classConstFetch = $this->convertNumberToConstant($return->expr);
            if (!$classConstFetch instanceof ClassConstFetch) {
                continue;
            }
            $hasChanged = \true;
            $return->expr = $classConstFetch;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function convertNumberToConstant(Int_ $int): ?ClassConstFetch
    {
        if (!isset(SymfonyCommandConstantMap::RETURN_TO_CONST[$int->value])) {
            return null;
        }
        return $this->nodeFactory->createClassConstFetch(SymfonyClass::COMMAND, SymfonyCommandConstantMap::RETURN_TO_CONST[$int->value]);
    }
}
