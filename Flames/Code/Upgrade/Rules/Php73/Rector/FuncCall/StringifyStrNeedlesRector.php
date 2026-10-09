<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\StringifyStrNeedlesRectorTest
 */
final class StringifyStrNeedlesRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var string[]
     */
    private const array NEEDLE_STRING_SENSITIVE_FUNCTIONS = ['strpos', 'strrpos', 'stripos', 'strstr', 'stripos', 'strripos', 'strstr', 'strchr', 'strrchr', 'stristr'];
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_INT_IN_STR_NEEDLES;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Make needles explicit strings', [new CodeSample(<<<'CODE_SAMPLE'
$needle = 5;
$fivePosition = strpos('725', $needle);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$needle = 5;
$fivePosition = strpos('725', (string) $needle);
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
        if (!$this->isNames($node, self::NEEDLE_STRING_SENSITIVE_FUNCTIONS)) {
            return null;
        }
        if (!isset($node->args[1])) {
            return null;
        }
        if (!$node->args[1] instanceof Arg) {
            return null;
        }
        // is argument string?
        $needleArgValue = $node->args[1]->value;
        if ($needleArgValue instanceof InterpolatedString) {
            return null;
        }
        $needleType = $this->getType($needleArgValue);
        if ($needleType->isString()->yes()) {
            return null;
        }
        $node->args[1]->value = new String_($node->args[1]->value);
        return $node;
    }
}
