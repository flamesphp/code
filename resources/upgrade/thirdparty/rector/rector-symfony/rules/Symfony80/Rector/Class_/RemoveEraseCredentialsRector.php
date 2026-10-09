<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony80\Rector\Class_;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md#security
 *
 * @see \Flames\Code\Upgrade\Symfony80\Rector\Class_\RemoveEraseCredentialsRectorTest
 */
final class RemoveEraseCredentialsRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/security-core', '>=8.0');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused UserInterface::eraseCredentials() method, make it part of serialize if needed', [new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Security\Core\User\UserInterface;

final class User implements UserInterface
{
    public function eraseCredentials()
    {
        // some logic here
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Security\Core\User\UserInterface;

final class User implements UserInterface
{
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
    public function refactor(Node $node): ?Class_
    {
        if (!$this->doesImplementUserInterface($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->stmts as $key => $classStmt) {
            if (!$classStmt instanceof ClassMethod) {
                continue;
            }
            if (!$this->isName($classStmt, 'eraseCredentials')) {
                continue;
            }
            $classMethodStmts = $classStmt->stmts;
            unset($node->stmts[$key]);
            $hasChanged = \true;
            if ($classMethodStmts !== []) {
                // make part of serialize method if it exists
                $serializeClassMethod = $node->getMethod('serialize');
                if ($serializeClassMethod instanceof ClassMethod) {
                    $serializeClassMethod->stmts = array_merge($serializeClassMethod->stmts ?? [], $classMethodStmts);
                } else {
                    // or create serialize method
                    $node->stmts[] = $this->createSserializeClassMethod($classMethodStmts);
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function doesImplementUserInterface(Class_ $class): bool
    {
        $found = array_any($class->implements, fn($name) => $this->isName($name, SymfonyClass::USER_INTERFACE));
        return $found;
    }
    /**
     * @param mixed $classMethodStmts
     */
    private function createSserializeClassMethod($classMethodStmts): ClassMethod
    {
        $classMethod = new ClassMethod('serialize', ['stmts' => $classMethodStmts]);
        $classMethod->flags = Modifiers::PUBLIC;
        return $classMethod;
    }
}
