<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\DowngradePhp73\Tokenizer\FollowedByCommaAnalyzer;
use Flames\Code\Upgrade\DowngradePhp73\Tokenizer\TrailingCommaRemover;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeTrailingCommasInParamUseRectorTest
 */
final class DowngradeTrailingCommasInParamUseRector extends AbstractRector
{
    public function __construct(private readonly FollowedByCommaAnalyzer $followedByCommaAnalyzer, private readonly TrailingCommaRemover $trailingCommaRemover)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove trailing commas in param or use list', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(string $value1, string $value2,)
    {
        function (string $value1, string $value2,) {
        };

        function () use ($value1, $value2,) {
        };
    }
}

function inFunction(string $value1, string $value2,)
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(string $value1, string $value2)
    {
        function (string $value1, string $value2) {
        };

        function () use ($value1, $value2) {
        };
    }
}

function inFunction(string $value1, string $value2)
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
        return [ClassMethod::class, Function_::class, Closure::class];
    }
    /**
     * @param ClassMethod|Function_|Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Closure) {
            $this->processUses($node);
        }
        return $this->processParams($node);
    }
    private function processUses(Closure $node): ?Node
    {
        if ($node->uses === []) {
            return null;
        }
        return $this->cleanTrailingComma($node, $node->uses);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $node
     */
    private function processParams($node): ?Node
    {
        if ($node->params === []) {
            return null;
        }
        return $this->cleanTrailingComma($node, $node->params);
    }
    /**
     * @param ClosureUse[]|Param[] $array
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $node
     */
    private function cleanTrailingComma($node, array $array): ?Node
    {
        $lastPosition = array_key_last($array);
        if ($lastPosition === null) {
            return null;
        }
        $last = $array[$lastPosition];
        if (!$this->followedByCommaAnalyzer->isFollowed($this->file, $last)) {
            return null;
        }
        $this->trailingCommaRemover->remove($this->file, $last);
        return $node;
    }
}
