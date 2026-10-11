<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as it copies docblock from another method call. The docblock can be incorrect or outdated, and spreads the error further.
 */
final class AddReturnDocblockFromMethodCallDocblockRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @return docblock based on detailed type of method call docblock', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}

final class Repository
{
    /**
     * @return SomeEntity[]
     */
    public function findAll(): array
    {
        // ...
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeController
{
    /**
     * @return SomeEntity[]
     */
    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}

final class Repository
{
    /**
     * @return SomeEntity[]
     */
    public function findAll(): array
    {
        // ...
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" is deprecated, as it copies docblock from another method call that can be incorrect or outdated', self::class));
    }
}
