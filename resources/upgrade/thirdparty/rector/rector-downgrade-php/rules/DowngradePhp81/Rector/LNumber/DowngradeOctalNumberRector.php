<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\LNumber;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://php.watch/versions/8.1/explicit-octal-notation
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\LNumber\DowngradeOctalNumberRectorTest
 */
final class DowngradeOctalNumberRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrades octal numbers to decimal ones', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return 0o777;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return 0777;
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
        return [Int_::class];
    }
    /**
     * @param Int_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $numberKind = $node->getAttribute(AttributeKey::KIND);
        if ($numberKind !== Int_::KIND_OCT) {
            return null;
        }
        /** @var string $rawValue */
        $rawValue = $node->getAttribute(AttributeKey::RAW_VALUE);
        if (!str_starts_with($rawValue, '0o') && !str_starts_with($rawValue, '0O')) {
            return null;
        }
        $clearValue = '0' . substr($rawValue, 2);
        $node->setAttribute(AttributeKey::RAW_VALUE, $clearValue);
        // invoke reprint
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        return $node;
    }
}
