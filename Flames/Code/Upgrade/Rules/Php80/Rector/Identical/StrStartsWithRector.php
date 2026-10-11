<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Rector\Identical;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\Rules\Php80\Contract\StrStartWithMatchAndRefactorInterface;
use Flames\Code\Upgrade\Rules\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor\StrncmpMatchAndRefactor;
use Flames\Code\Upgrade\Rules\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor\StrposMatchAndRefactor;
use Flames\Code\Upgrade\Rules\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor\SubstrMatchAndRefactor;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\StrStartsWith;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\ValueObject\PolyfillPackage;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\RelatedPolyfillInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Identical\StrStartsWithRectorTest
 */
final class StrStartsWithRector extends AbstractRector implements MinPhpVersionInterface, RelatedPolyfillInterface
{
    /**
     * @var StrStartWithMatchAndRefactorInterface[]
     */
    private readonly array $strStartWithMatchAndRefactors;
    public function __construct(StrncmpMatchAndRefactor $strncmpMatchAndRefactor, SubstrMatchAndRefactor $substrMatchAndRefactor, StrposMatchAndRefactor $strposMatchAndRefactor)
    {
        $this->strStartWithMatchAndRefactors = [$strncmpMatchAndRefactor, $substrMatchAndRefactor, $strposMatchAndRefactor];
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::STR_STARTS_WITH;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change helper functions to str_starts_with()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $isMatch = substr($haystack, 0, strlen($needle)) === $needle;

        $isNotMatch = substr($haystack, 0, strlen($needle)) !== $needle;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $isMatch = str_starts_with($haystack, $needle);

        $isNotMatch = ! str_starts_with($haystack, $needle);
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
        return [Identical::class, NotIdentical::class, Equal::class, NotEqual::class];
    }
    /**
     * @param Identical|NotIdentical|Equal|NotEqual $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->strStartWithMatchAndRefactors as $strStartWithMatchAndRefactor) {
            $strStartsWithValueObject = $strStartWithMatchAndRefactor->match($node);
            if (!$strStartsWithValueObject instanceof StrStartsWith) {
                continue;
            }
            return $strStartWithMatchAndRefactor->refactorStrStartsWith($strStartsWithValueObject);
        }
        return null;
    }
    public function providePolyfillPackage(): string
    {
        return PolyfillPackage::PHP_80;
    }
}
