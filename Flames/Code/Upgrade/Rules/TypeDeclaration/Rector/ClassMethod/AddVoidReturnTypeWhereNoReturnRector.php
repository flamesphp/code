<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ClassModifierChecker;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\SilentVoidResolver;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnVendorLockResolver;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRectorTest
 */
final class AddVoidReturnTypeWhereNoReturnRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly SilentVoidResolver $silentVoidResolver, private readonly ClassMethodReturnVendorLockResolver $classMethodReturnVendorLockResolver, private readonly ClassModifierChecker $classModifierChecker)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type void to function like without any return', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function getValues()
    {
        $value = 1000;
        return;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function getValues(): void
    {
        $value = 1000;
        return;
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
        // already has return type → skip
        if ($node->returnType instanceof Node) {
            return null;
        }
        if ($this->shouldSkipClassMethod($node)) {
            return null;
        }
        if (!$this->silentVoidResolver->hasExclusiveVoid($node)) {
            return null;
        }
        if ($this->classMethodReturnVendorLockResolver->isVendorLocked($node)) {
            return null;
        }
        $node->returnType = new Identifier('void');
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::VOID_TYPE;
    }
    private function shouldSkipClassMethod(ClassMethod $classMethod): bool
    {
        if ($classMethod->isAbstract()) {
            return \true;
        }
        // is not final and has only exception? possibly implemented by child
        if ($this->isNotFinalAndHasExceptionOnly($classMethod)) {
            return \true;
        }
        // possibly required by child implementation
        if ($this->isNotFinalAndEmpty($classMethod)) {
            return \true;
        }
        if ($classMethod->isProtected()) {
            return !$this->classModifierChecker->isInsideFinalClass($classMethod);
        }
        $scope = ScopeFetcher::fetch($classMethod);
        if (!$scope->isInClass()) {
            return \false;
        }
        $classReflection = $scope->getClassReflection();
        return $classReflection->isAbstract();
    }
    private function isNotFinalAndHasExceptionOnly(ClassMethod $classMethod): bool
    {
        if ($this->classModifierChecker->isInsideFinalClass($classMethod)) {
            return \false;
        }
        if (count((array) $classMethod->stmts) !== 1) {
            return \false;
        }
        $onlyStmt = $classMethod->stmts[0] ?? null;
        return $onlyStmt instanceof Expression && $onlyStmt->expr instanceof Throw_;
    }
    private function isNotFinalAndEmpty(ClassMethod $classMethod): bool
    {
        if ($this->classModifierChecker->isInsideFinalClass($classMethod)) {
            return \false;
        }
        return $classMethod->stmts === [];
    }
}
