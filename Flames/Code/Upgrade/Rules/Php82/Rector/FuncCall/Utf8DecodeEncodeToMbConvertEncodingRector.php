<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php82\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://3v4l.org/Q14UR
 * @see \Flames\Code\Upgrade\Rules\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRectorTest
 */
final class Utf8DecodeEncodeToMbConvertEncodingRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change deprecated `utf8_decode()` and `utf8_encode()` to `mb_convert_encoding()`', [new CodeSample(<<<'CODE_SAMPLE'
utf8_decode($value);
utf8_encode($value);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
mb_convert_encoding($value, 'ISO-8859-1');
mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if ($this->isName($node, 'utf8_decode')) {
            $node->name = new Name('mb_convert_encoding');
            $node->args[1] = new Arg(new String_('ISO-8859-1'));
            return $node;
        }
        if ($this->isName($node, 'utf8_encode')) {
            $node->name = new Name('mb_convert_encoding');
            $node->args[1] = new Arg(new String_('UTF-8'));
            $node->args[2] = new Arg(new String_('ISO-8859-1'));
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_UTF8_DECODE_ENCODE_FUNCTION;
    }
}
