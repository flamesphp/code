<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\ArrayDimFetch;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\Application\File;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\ArrayDimFetch\CurlyToSquareBracketArrayStringRectorTest
 */
final class CurlyToSquareBracketArrayStringRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_CURLY_BRACKET_ARRAY_STRING;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change curly based array and string to square bracket', [new CodeSample(<<<'CODE_SAMPLE'
$string = 'test';
echo $string{0};

$array = ['test'];
echo $array{0};
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$string = 'test';
echo $string[0];

$array = ['test'];
echo $array[0];
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ArrayDimFetch::class];
    }
    /**
     * @param ArrayDimFetch $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isFollowedByCurlyBracket($this->getFile(), $node)) {
            return null;
        }
        // re-draw the ArrayDimFetch to use [] bracket
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        return $node;
    }
    private function isFollowedByCurlyBracket(File $file, ArrayDimFetch $arrayDimFetch): bool
    {
        $oldTokens = $file->getOldTokens();
        $endTokenPost = $arrayDimFetch->getEndTokenPos();
        if (isset($oldTokens[$endTokenPost]) && (string) $oldTokens[$endTokenPost] === '}') {
            $startTokenPos = $arrayDimFetch->getStartTokenPos();
            return !isset($oldTokens[$startTokenPos]) || (string) $oldTokens[$startTokenPos] !== '${';
        }
        return \false;
    }
}
