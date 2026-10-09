<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp85\Rector\Class_;

use PhpParser\Builder\Property;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\Visibility;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/final_promotion
 *
 * @see \Flames\Code\Upgrade\DowngradePhp85\Rector\Class_\DowngradeFinalPropertyPromotionRectorTest
 */
final class DowngradeFinalPropertyPromotionRector extends AbstractRector
{
    private const string TAGNAME = 'final';
    public function __construct(private readonly VisibilityManipulator $visibilityManipulator, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change constructor final property promotion to @final tag', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        final public string $id
    ){}
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        /** @final */
        public string $id
    ) {}
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
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$this->isName($node, MethodName::CONSTRUCT)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->params as $param) {
            if (!$param->isPromoted()) {
                continue;
            }
            if (!$this->visibilityManipulator->hasVisibility($param, Visibility::FINAL)) {
                continue;
            }
            $hasChanged = \true;
            $this->visibilityManipulator->makeNonFinal($param);
            if (!$param->isPromoted()) {
                $this->visibilityManipulator->makePublic($param);
            }
            $this->addPhpDocTag($param);
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param \PhpParser\Builder\Property|\PhpParser\Node\Param $node
     */
    private function addPhpDocTag($node): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        if ($phpDocInfo->hasByName(self::TAGNAME)) {
            return;
        }
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@' . self::TAGNAME, new GenericTagValueNode('')));
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
    }
}
