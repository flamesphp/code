<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php54\Rector\Array_;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php54\Rector\Array_\LongArrayToShortArrayRectorTest
 */
final class LongArrayToShortArrayRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SHORT_ARRAY;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Long array to short array', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return array();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return [];
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
        return [Array_::class];
    }
    /**
     * @param Array_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // no kind attribute yet, it means just created
        // no need to reprint, it already will be short array by default
        if (!$node->hasAttribute(AttributeKey::KIND)) {
            return null;
        }
        if ($node->getAttribute(AttributeKey::KIND) === Array_::KIND_SHORT) {
            return null;
        }
        $node->setAttribute(AttributeKey::KIND, Array_::KIND_SHORT);
        $tokens = $this->getFile()->getOldTokens();
        $startTokenPos = $node->getStartTokenPos();
        $endTokenPos = $node->getEndTokenPos();
        if (!isset($tokens[$startTokenPos], $tokens[$endTokenPos])) {
            return null;
        }
        // replace array opening
        $tokens[$startTokenPos]->text = '';
        $iteration = 1;
        while (isset($tokens[$startTokenPos + $iteration])) {
            if (trim($tokens[$startTokenPos + $iteration]->text) === '') {
                ++$iteration;
                continue;
            }
            if (trim($tokens[$startTokenPos + $iteration]->text) !== '(') {
                break;
            }
            // replace ( parentheses opening
            $tokens[$startTokenPos + $iteration]->text = '[';
            // replace ) parentheses closing
            $tokens[$endTokenPos]->text = ']';
            break;
        }
        return $node;
    }
}
