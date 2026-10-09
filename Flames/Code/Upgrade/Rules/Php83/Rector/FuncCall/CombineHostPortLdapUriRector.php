<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\InterpolatedStringPart;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://www.php.net/manual/en/migration83.deprecated.php#migration83.deprecated.ldap
 * @see \Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\CombineHostPortLdapUriRectorTest
 */
final class CombineHostPortLdapUriRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ExprAnalyzer $exprAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Combine separated host and port on ldap_connect() args', [new CodeSample(<<<'CODE_SAMPLE'
ldap_connect('ldap://ldap.example.com', 389);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
ldap_connect('ldap://ldap.example.com:389');
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
        if (!$this->isName($node, 'ldap_connect')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 2) {
            return null;
        }
        $firstArg = $args[0]->value;
        $secondArg = $args[1]->value;
        if ($firstArg instanceof String_ && $secondArg instanceof Int_) {
            $args[0]->value = new String_($firstArg->value . ':' . $secondArg->value);
        } elseif ($this->exprAnalyzer->isDynamicExpr($firstArg) && $this->exprAnalyzer->isDynamicExpr($secondArg)) {
            if ($firstArg instanceof Concat && !$secondArg instanceof Concat) {
                $args[0]->value = new Concat($firstArg, new String_(':'));
                $args[0]->value = new Concat($args[0]->value, $secondArg);
            } else {
                $args[0]->value = new InterpolatedString([$firstArg, new InterpolatedStringPart(':'), $secondArg]);
            }
        } else {
            return null;
        }
        unset($args[1]);
        $node->args = $args;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_HOST_PORT_SEPARATE_ARGS;
    }
}
