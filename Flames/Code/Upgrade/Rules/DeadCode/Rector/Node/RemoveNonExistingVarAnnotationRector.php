<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\InlineHTML;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Static_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRectorTest
 */
final class RemoveNonExistingVarAnnotationRector extends AbstractRector
{
    /**
     * @var array<class-string<Stmt>>
     */
    private const array NODE_TYPES = [Foreach_::class, Static_::class, Echo_::class, Return_::class, Expression::class, If_::class, While_::class, Switch_::class, Nop::class];
    public function __construct(private readonly StmtsManipulator $stmtsManipulator, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ValueResolver $valueResolver, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes non-existing @var annotations above the code', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function get()
    {
        /** @var Training[] $trainings */
        return $this->getData();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function get()
    {
        return $this->getData();
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
        return NodeGroup::STMTS_AWARE;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }
        $hasChanged = \false;
        $extractValues = [];
        foreach ($node->stmts as $key => $stmt) {
            $hasChangedStmt = \false;
            if ($stmt instanceof Expression && $stmt->expr instanceof FuncCall && $this->isName($stmt->expr, 'extract') && !$stmt->expr->isFirstClassCallable()) {
                $appendExtractValues = $this->valueResolver->getValue($stmt->expr->getArgs()[0]->value);
                if (!is_array($appendExtractValues)) {
                    // nothing can do as value is dynamic
                    break;
                }
                $extractValues = array_merge($extractValues, array_keys($appendExtractValues));
                continue;
            }
            if ($stmt instanceof Static_) {
                foreach ($stmt->vars as $staticVar) {
                    $staticVarName = $this->getName($staticVar->var);
                    if ($staticVarName === null) {
                        continue;
                    }
                    $extractValues[] = $staticVarName;
                }
            }
            if ($this->shouldSkip($node, $key, $stmt, $extractValues)) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($stmt);
            $varTagValueNodes = $phpDocInfo->getPhpDocNode()->getVarTagValues();
            foreach ($varTagValueNodes as $varTagValueNode) {
                if ($this->isObjectShapePseudoType($varTagValueNode)) {
                    continue;
                }
                $variableName = ltrim($varTagValueNode->variableName, '$');
                if ($variableName === '' && $this->isAllowedEmptyVariableName($stmt, $varTagValueNode)) {
                    continue;
                }
                if ($this->hasVariableName($stmt, $variableName)) {
                    continue;
                }
                $comments = $node->getComments();
                if (isset($comments[1])) {
                    // skip edge case with double comment, as impossible to resolve by PHPStan doc parser
                    continue;
                }
                if ($this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, $variableName)) {
                    continue;
                }
                if ($variableName === '') {
                    $phpDocInfo->removeByType(VarTagValueNode::class);
                } else {
                    $phpDocInfo->removeByType(VarTagValueNode::class, $variableName);
                }
                $hasChangedStmt = \true;
            }
            if ($hasChangedStmt) {
                $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($stmt);
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param StmtsAware $stmtsAware
     * @param string[] $extractValues
     */
    private function shouldSkip(Node $stmtsAware, int $key, Stmt $stmt, array $extractValues): bool
    {
        if (!in_array($stmt::class, self::NODE_TYPES, \true)) {
            return \true;
        }
        if (count($stmt->getComments()) !== 1) {
            return \true;
        }
        foreach ($extractValues as $extractValue) {
            if ($this->stmtsManipulator->isVariableUsedInNextStmt($stmtsAware, $key + 1, $extractValue)) {
                return \true;
            }
        }
        return isset($stmtsAware->stmts[$key + 1]) && $stmtsAware->stmts[$key + 1] instanceof InlineHTML;
    }
    private function hasVariableName(Stmt $stmt, string $variableName): bool
    {
        return (bool) $this->betterNodeFinder->findFirst($stmt, function (Node $node) use ($variableName): bool {
            if (!$node instanceof Variable) {
                return \false;
            }
            return $this->isName($node, $variableName);
        });
    }
    /**
     * This is a hack,
     * that waits on phpdoc-parser to get merged - https://github.com/phpstan/phpdoc-parser/pull/145
     */
    private function isObjectShapePseudoType(VarTagValueNode $varTagValueNode): bool
    {
        if (!$varTagValueNode->type instanceof IdentifierTypeNode) {
            return \false;
        }
        if ($varTagValueNode->type->name !== 'object') {
            return \false;
        }
        if (!str_starts_with($varTagValueNode->description, '{')) {
            return \false;
        }
        return str_contains($varTagValueNode->description, '}');
    }
    private function isAllowedEmptyVariableName(Stmt $stmt, VarTagValueNode $varTagValueNode): bool
    {
        if ($stmt instanceof Return_ && $stmt->expr instanceof CallLike && !$stmt->expr instanceof New_) {
            return \true;
        }
        // generic/template narrowing over the instantiated class, e.g. /** @var self<TValue, never> */ return new self(...)
        if ($stmt instanceof Return_ && $stmt->expr instanceof New_ && $this->isGenericTypeOfNewClass($stmt->expr, $varTagValueNode)) {
            return \true;
        }
        return $stmt instanceof Expression && $stmt->expr instanceof Assign && $stmt->expr->var instanceof Variable;
    }
    private function isGenericTypeOfNewClass(New_ $new, VarTagValueNode $varTagValueNode): bool
    {
        if (!$varTagValueNode->type instanceof GenericTypeNode) {
            return \false;
        }
        $newClassName = $this->getName($new->class);
        if ($newClassName === null) {
            return \false;
        }
        return strcasecmp($varTagValueNode->type->type->name, $newClassName) === 0;
    }
}
