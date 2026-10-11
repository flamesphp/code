<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php82\Rector\Encapsed;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRectorTest
 */
final class VariableInStringInterpolationFixerRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace deprecated `${var}` to `{$var}`', [new CodeSample(<<<'CODE_SAMPLE'
$c = "football";
echo "I like playing ${c}";
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$c = "football";
echo "I like playing {$c}";
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [InterpolatedString::class];
    }
    /**
     * @param InterpolatedString $node
     */
    public function refactor(Node $node): ?Node
    {
        $oldTokens = $this->getFile()->getOldTokens();
        $hasChanged = \false;
        foreach ($node->parts as $part) {
            if (!$part instanceof Variable && (!$part instanceof ArrayDimFetch || !$part->var instanceof Variable)) {
                continue;
            }
            $startTokenPos = $part->getStartTokenPos();
            if (!isset($oldTokens[$startTokenPos])) {
                continue;
            }
            if ((string) $oldTokens[$startTokenPos] !== '${') {
                continue;
            }
            if ($part instanceof Variable) {
                $part->setAttribute(AttributeKey::ORIGINAL_NODE, null);
            } else {
                $oldTokens[$startTokenPos]->text = '{$';
            }
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_VARIABLE_IN_STRING_INTERPOLATION;
    }
}
