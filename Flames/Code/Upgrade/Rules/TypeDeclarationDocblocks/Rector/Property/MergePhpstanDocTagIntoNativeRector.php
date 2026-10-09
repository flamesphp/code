<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTextNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRectorTest
 */
final class MergePhpstanDocTagIntoNativeRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const array PHPSTAN_TAG_TO_NATIVE_TAG = ['@phpstan-var' => '@var', '@phpstan-param' => '@param', '@phpstan-return' => '@return'];
    public function __construct(private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Merge more precise @phpstan-var/@phpstan-param/@phpstan-return docblock tag into its native @var/@param/@return tag', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var Collection
     *
     * @phpstan-var Collection<int, string>
     */
    private $items;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var Collection<int, string>
     */
    private $items;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class, Param::class, ClassConst::class, ClassMethod::class, Function_::class];
    }
    /**
     * @param Property|Param|ClassConst|ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $phpDocNode = $phpDocInfo->getPhpDocNode();
        $phpstanParamNames = [];
        $phpstanVarNames = [];
        $hasPhpstanReturn = \false;
        foreach ($phpDocNode->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if ($phpDocChildNode->name === '@phpstan-param' && $phpDocChildNode->value instanceof ParamTagValueNode) {
                $phpstanParamNames[] = $phpDocChildNode->value->parameterName;
            } elseif ($phpDocChildNode->name === '@phpstan-var' && $phpDocChildNode->value instanceof VarTagValueNode) {
                $phpstanVarNames[] = $phpDocChildNode->value->variableName;
            } elseif ($phpDocChildNode->name === '@phpstan-return' && $phpDocChildNode->value instanceof ReturnTagValueNode) {
                $hasPhpstanReturn = \true;
            }
        }
        if ($phpstanParamNames === [] && $phpstanVarNames === [] && !$hasPhpstanReturn) {
            return null;
        }
        $hasChanged = \false;
        foreach ($phpDocNode->children as $key => $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            // drop the weaker native tag that the @phpstan-* variant overrules
            if ($this->isOverruledNativeTag($phpDocChildNode, $phpstanParamNames, $phpstanVarNames, $hasPhpstanReturn)) {
                unset($phpDocNode->children[$key]);
                $hasChanged = \true;
                continue;
            }
            // promote the @phpstan-* tag to its native counterpart
            $nativeTagName = self::PHPSTAN_TAG_TO_NATIVE_TAG[$phpDocChildNode->name] ?? null;
            if ($nativeTagName !== null) {
                $phpDocNode->children[$key] = new PhpDocTagNode($nativeTagName, $phpDocChildNode->value);
                $hasChanged = \true;
            }
        }
        if (!$hasChanged) {
            return null;
        }
        $phpDocNode->children = array_values($phpDocNode->children);
        // drop a leading empty line left behind by a removed native tag
        while ($phpDocNode->children !== []) {
            $firstChildNode = $phpDocNode->children[0];
            if ($firstChildNode instanceof PhpDocTextNode && trim($firstChildNode->text) === '') {
                array_shift($phpDocNode->children);
                continue;
            }
            break;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    /**
     * @param string[] $phpstanParamNames
     * @param string[] $phpstanVarNames
     */
    private function isOverruledNativeTag(PhpDocTagNode $phpDocTagNode, array $phpstanParamNames, array $phpstanVarNames, bool $hasPhpstanReturn): bool
    {
        if ($phpDocTagNode->name === '@param' && $phpDocTagNode->value instanceof ParamTagValueNode) {
            return in_array($phpDocTagNode->value->parameterName, $phpstanParamNames, \true);
        }
        if ($phpDocTagNode->name === '@var' && $phpDocTagNode->value instanceof VarTagValueNode) {
            return in_array($phpDocTagNode->value->variableName, $phpstanVarNames, \true);
        }
        if ($phpDocTagNode->name === '@return' && $phpDocTagNode->value instanceof ReturnTagValueNode) {
            return $hasPhpstanReturn;
        }
        return \false;
    }
}
