<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ArrayCallbackParamTypeResolver;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FuncCall\NarrowArrayAnyAllNullableParamTypeRectorTest
 */
final class NarrowArrayAnyAllNullableParamTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var string[]
     */
    private const array FUNCTION_NAMES = ['array_any', 'array_all', 'array_find', 'array_find_key'];
    public function __construct(private readonly ArrayCallbackParamTypeResolver $arrayCallbackParamTypeResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Narrow an already nullable array_any()/array_all()/array_find()/array_find_key() closure param to the non-nullable array item type', [new CodeSample(<<<'CODE_SAMPLE'
/** @var string[] $items */
array_any($items, fn (?string $item): bool => $item !== '');
CODE_SAMPLE
, <<<'CODE_SAMPLE'
/** @var string[] $items */
array_any($items, fn (string $item): bool => $item !== '');
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
        return $this->arrayCallbackParamTypeResolver->refactorFirstParamType($node, self::FUNCTION_NAMES, \true);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARRAY_ANY;
    }
}
