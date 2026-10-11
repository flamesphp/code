<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\NullsafeMethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\ReturnStrictTypeAnalyzer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/nullsafe_operator
 *
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRectorTest
 */
final class CleanupUnneededNullsafeOperatorRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReturnStrictTypeAnalyzer $returnStrictTypeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Cleanup unneeded nullsafe operator', [new CodeSample(<<<'CODE_SAMPLE'
class HelloWorld {
    public function getString(): string
    {
         return 'hello world';
    }
}

function get(): HelloWorld
{
     return new HelloWorld();
}

echo get()?->getString();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class HelloWorld {
    public function getString(): string
    {
         return 'hello world';
    }
}

function get(): HelloWorld
{
     return new HelloWorld();
}

echo get()->getString();
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [NullsafeMethodCall::class];
    }
    /**
     * @param NullsafeMethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->name instanceof Identifier) {
            return null;
        }
        if (!$node->var instanceof FuncCall && !$node->var instanceof MethodCall && !$node->var instanceof StaticCall) {
            return null;
        }
        $returnType = $this->returnStrictTypeAnalyzer->resolveMethodCallReturnType($node->var);
        if (!$returnType instanceof ObjectType) {
            return null;
        }
        return new MethodCall($node->var, $node->name, $node->args);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NULLSAFE_OPERATOR;
    }
}
