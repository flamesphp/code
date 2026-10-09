<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\Interface_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\FamilyTree\Reflection\FamilyRelationsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\Interface_\DowngradePreviouslyImplementedInterfaceRectorTest
 */
final class DowngradePreviouslyImplementedInterfaceRector extends AbstractRector
{
    public function __construct(private readonly FamilyRelationsAnalyzer $familyRelationsAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade previously implemented interface', [new CodeSample(<<<'CODE_SAMPLE'
interface ContainerExceptionInterface extends Throwable
{
}

interface ExceptionInterface extends ContainerExceptionInterface, Throwable
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
interface ContainerExceptionInterface extends Throwable
{
}

interface ExceptionInterface extends ContainerExceptionInterface
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
        return [Interface_::class];
    }
    /**
     * @param Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $extends = $node->extends;
        if ($extends === []) {
            return null;
        }
        if (count($extends) === 1) {
            return null;
        }
        $collectInterfaces = [];
        $isCleaned = \false;
        foreach ($extends as $key => $extend) {
            if (!$extend instanceof FullyQualified) {
                continue;
            }
            if (in_array($extend->toString(), $collectInterfaces, \true)) {
                unset($extends[$key]);
                $isCleaned = \true;
                continue;
            }
            $collectInterfaces = array_merge($collectInterfaces, $this->familyRelationsAnalyzer->getClassLikeAncestorNames($extend));
        }
        if (!$isCleaned) {
            return null;
        }
        $node->extends = $extends;
        return $node;
    }
}
