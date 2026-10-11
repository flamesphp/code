<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php71\Rector\BooleanOr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\Rules\Php71\IsArrayAndDualCheckToAble;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php71\Rector\BooleanOr\IsIterableRectorTest
 */
final class IsIterableRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly IsArrayAndDualCheckToAble $isArrayAndDualCheckToAble, private readonly ReflectionProvider $reflectionProvider, private readonly PhpVersionProvider $phpVersionProvider)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::IS_ITERABLE;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes is_array + Traversable check to is_iterable', [new CodeSample('is_array($foo) || $foo instanceof Traversable;', 'is_iterable($foo);')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanOr::class];
    }
    /**
     * @param BooleanOr $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip()) {
            return null;
        }
        return $this->isArrayAndDualCheckToAble->processBooleanOr($node, 'Traversable', 'is_iterable');
    }
    private function shouldSkip(): bool
    {
        if ($this->reflectionProvider->hasFunction(new Name('is_iterable'), null)) {
            return \false;
        }
        return !$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::IS_ITERABLE);
    }
}
