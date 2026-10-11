<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp73\Rector\String_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\DowngradePhp73\Tokenizer\FollowedByNewlineOnlyMaybeWithSemicolonAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp73\Rector\String_\DowngradeFlexibleHeredocSyntaxRectorTest
 */
final class DowngradeFlexibleHeredocSyntaxRector extends AbstractRector
{
    /**
     * @var int[]
     */
    private const array HERENOW_DOC_KINDS = [String_::KIND_HEREDOC, String_::KIND_NOWDOC];
    public function __construct(private readonly FollowedByNewlineOnlyMaybeWithSemicolonAnalyzer $followedByNewlineOnlyMaybeWithSemicolonAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove indentation from heredoc/nowdoc', [new CodeSample(<<<'CODE_SAMPLE'
$query = <<<SQL
    SELECT *
    FROM `table`
    WHERE `column` = true;
    SQL;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$query = <<<SQL
SELECT *
FROM `table`
WHERE `column` = true;
SQL;
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [String_::class, InterpolatedString::class];
    }
    /**
     * @param InterpolatedString|String_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $stringKind = $node->getAttribute(AttributeKey::KIND);
        if (!in_array($stringKind, self::HERENOW_DOC_KINDS, \true)) {
            return null;
        }
        // skip correctly indented
        $docIndentation = (string) $node->getAttribute(AttributeKey::DOC_INDENTATION);
        if ($docIndentation === '' && $this->followedByNewlineOnlyMaybeWithSemicolonAnalyzer->isFollowed($this->file, $node)) {
            return null;
        }
        $node->setAttribute(AttributeKey::DOC_INDENTATION, '__REMOVED__');
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        $tokens = $this->file->getOldTokens();
        if (isset($tokens[$node->getEndTokenPos()], $tokens[$node->getEndTokenPos() + 1])) {
            $tokens[$node->getEndTokenPos() + 1]->text = "\n" . $tokens[$node->getEndTokenPos() + 1]->text;
        }
        return $node;
    }
}
