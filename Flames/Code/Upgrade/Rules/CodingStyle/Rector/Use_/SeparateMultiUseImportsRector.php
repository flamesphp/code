<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Use_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TraitUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\Use_\SeparateMultiUseImportsRectorTest
 */
final class SeparateMultiUseImportsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Split multi use imports and trait statements to standalone lines', [new CodeSample(<<<'CODE_SAMPLE'
use A, B;

class SomeClass
{
    use SomeTrait, AnotherTrait;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use A;
use B;

class SomeClass
{
    use SomeTrait;
    use AnotherTrait;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class, Namespace_::class, Class_::class];
    }
    /**
     * @param FileNode|Namespace_|Class_ $node
     * @return \Flames\Code\Upgrade\PhpParser\Node\FileNode|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|null
     */
    public function refactor(Node $node)
    {
        if ($node instanceof FileNode && $node->isNamespaced()) {
            // handled in Namespace_
            return null;
        }
        $hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Use_) {
                $refactorUseImport = $this->refactorUseImport($stmt);
                if ($refactorUseImport !== null) {
                    unset($node->stmts[$key]);
                    array_splice($node->stmts, $key, 0, $refactorUseImport);
                    $hasChanged = \true;
                }
                continue;
            }
            if ($stmt instanceof TraitUse) {
                $refactorTraitUse = $this->refactorTraitUse($stmt);
                if ($refactorTraitUse !== null) {
                    unset($node->stmts[$key]);
                    array_splice($node->stmts, $key, 0, $refactorTraitUse);
                    $hasChanged = \true;
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @return Use_[]|null $use
     */
    private function refactorUseImport(Use_ $use): ?array
    {
        if (count($use->uses) < 2) {
            return null;
        }
        $uses = [];
        foreach ($use->uses as $singleUse) {
            $uses[] = new Use_([$singleUse]);
        }
        return $uses;
    }
    /**
     * @return TraitUse[]|null
     */
    private function refactorTraitUse(TraitUse $traitUse): ?array
    {
        if (count($traitUse->traits) < 2) {
            return null;
        }
        $traitNames = array_map(static fn(Name $name): string => $name->toString(), $traitUse->traits);
        foreach ($traitUse->adaptations as $traitAdaptation) {
            // an adaptation that names no trait applies to all of them, and one that
            // names a trait used elsewhere belongs to no statement here; either way it
            // cannot follow a single trait, so the statement is left alone
            if (!$traitAdaptation->trait instanceof Name) {
                return null;
            }
            if (!in_array($traitAdaptation->trait->toString(), $traitNames, \true)) {
                return null;
            }
        }
        $traitUses = [];
        foreach ($traitUse->traits as $singleTraitUse) {
            $adaptation = [];
            foreach ($traitUse->adaptations as $traitAdaptation) {
                if ($traitAdaptation->trait instanceof Name && $traitAdaptation->trait->toString() === $singleTraitUse->toString()) {
                    $adaptation[] = $traitAdaptation;
                }
            }
            $traitUses[] = new TraitUse([$singleTraitUse], $adaptation);
        }
        return $traitUses;
    }
}
